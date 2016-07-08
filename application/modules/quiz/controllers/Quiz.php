<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Quiz extends MY_Controller {

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        $this->load->model('quiz/Quiz_model');
        $this->load->model('quiz/Quiz_question_options_model');
        $this->load->model('quiz/Quiz_questions_model');
        $this->load->model('quiz/Quiz_exam_model');
        $this->load->model('quiz/Quiz_result_model');
    }

    /**
     * Index
     */
    function index() {
        $this->data['title'] = 'Quiz';
        $this->data['page'] = 'quiz';
        $this->data['quiz'] = $this->Quiz_model->quiz_details();
        
        $this->session->unset_userdata('quiz_id');
        $this->__template('quiz/index', $this->data);
    }

    /**
     * Index action
     */
    function create() {
        if ($_POST) {
            $insert_id = $this->Quiz_model->insert(array(
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'department_id' => $_POST['department'],
                'branch_id' => $_POST['branch'],
                'batch_id' => $_POST['batch'],
                'semester_id' => $_POST['semester'],
                'validity_type' => $_POST['validity_type'],
                'validity_value' => $_POST['validity_value'],
                'total_marks' => $_POST['total_marks'],
                'nagative_mark_status' => $_POST['nagative_marks_status'],
                'nagative_mark' => $_POST['nagative_marks'],
                'total_questions' => $_POST['total_questions'],
                'timer_status' => $_POST['timer_status'],
                'timer_value' => $_POST['timer_value'],
                'difficulty_level' => $_POST['difficulty_level'],
                'start_date' => date('Y-m-d', strtotime($_POST['start_date'])),
                'end_date' => date('Y-m-d', strtotime($_POST['end_date'])),
                'status' => $_POST['status'],
                'result_date' => date('Y-m-d', strtotime($_POST['result_date']))
            ));
            $this->flash_notification('Quiz is successfully added.');

            redirect(base_url('quiz/add_quiz_questions/' . $insert_id));
        }

        redirect(base_url('quiz'));
    }

    /**
     * Add quiz questions
     * @param string $quiz_id
     */
    function add_quiz_questions($quiz_id = '') {
        if ($_POST) {
            $quiz = $this->Quiz_model->get($quiz_id);
            for ($i = 1; $i <= $quiz->total_questions; $i++) {
                //insert questions
                $question_id = $this->Quiz_questions_model->insert(array(
                    'quiz_id' => $quiz_id,
                    'question_type' => $_POST['question_type_' . $i],
                    'total_answer' => count(explode(',', $_POST['question_' . $i . '_answer'])),
                    'question' => $_POST['question_' . $i],
                    'currect_answer' => $_POST['question_' . $i . '_answer'],
                    'answer_description' => ''
                ));

                //insert question
                for ($j = 1; $j <= 4; $j++) {
                    $option_id = $this->Quiz_question_options_model->insert(array(
                        'quiz_id' => $quiz_id,
                        'quiz_question_id' => $question_id,
                        'option_no' => $j,
                        'question_option' => $_POST['question_' . $i . '_option_' . $j]
                    ));
                }
            }
            $this->flash_notification('Quiz questions is successfully added.');

            redirect(base_url('quiz'));
        }
        $this->data['title'] = 'Add Quiz Questions';
        $this->data['page'] = 'question';
        $this->data['quiz'] = $this->Quiz_model->single_quiz_details($quiz_id);
        $this->__template('quiz/add_quiz_questions', $this->data);
    }

    /**
     * Play online quiz
     * @param string $quiz_id
     */
    function play_online_quiz($quiz_id) {
        //call validate function
        if ($this->session->userdata('quiz_id') != hash('sha1', $quiz_id)) {
            $is_avail_quiz = $this->Quiz_model->get($quiz_id);
            if ($is_avail_quiz) {
                redirect('quiz/instruction/' . $quiz_id);
            } else {
                redirect('quiz');
            }
        }
        if ($_POST) {
            //quiz status variables            
            $currect_answer = 0;
            $incurrect_answer = 0;
            $nagative_marks = 0.0;
            $total_marks = 0.0;

            $quiz = $this->Quiz_model->get($quiz_id);
            $quiz_questions = $this->Quiz_questions_model->get_many_by(array(
                'quiz_id' => $quiz_id
            ));

            $per_question_marks = number_format(($quiz->total_marks / count($quiz_questions)), 2);

            //user quiz answers
            foreach ($quiz_questions as $row) {
                $data = array(
                    'quiz_id' => $quiz_id,
                    'quiz_question_id' => $row->quiz_question_id,
                    'user_id' => $this->session->userdata('user_id')
                );
                if (isset($_POST['question_' . $row->quiz_question_id])) {
                    $data['answer'] = implode(',', $_POST['question_' . $row->quiz_question_id]);
                } else {
                    $data['answer'] = '';
                }

                //check for currect answer
                if ($data['answer'] == $row->currect_answer) {
                    $currect_answer++;
                } else {
                    if ($data['answer'] != '') {
                        $incurrect_answer++;
                    }
                }
                $this->Quiz_exam_model->insert($data);
            }

            //check for nagative marks
            if ($quiz->nagative_mark_status) {
                $nagative_marks = $incurrect_answer * ($quiz->nagative_mark * $per_question_marks);
            }

            $total_marks = ($per_question_marks * $currect_answer) - $nagative_marks;

            //check for nagative total marks
            if ($total_marks < 0) {
                $total_marks = 0;
            }

            //user result
            $this->Quiz_result_model->insert(array(
                'quiz_id' => $quiz_id,
                'user_id' => $this->session->userdata('user_id'),
                'total_question_attemps' => $currect_answer + $incurrect_answer,
                'currect_answers' => $currect_answer,
                'obtained_marks' => $total_marks
            ));
            $this->flash_notification('Exam is successfully submitted.');
            $this->session->unset_userdata('quiz_id');
            redirect(base_url('quiz'));
        }
        $this->data['title'] = 'Play online quiz';
        $this->data['page'] = 'quiz';
        $this->data['quiz'] = $this->Quiz_model->single_quiz_details($quiz_id);
        $this->__template('quiz/play_online_quiz', $this->data);
    }

    /**
     * Quiz instruction
     * @param type $id
     */
    function instruction($id = '') {
        if ($_POST) {
            $this->session->set_userdata('quiz_id', $_POST['quiz_id']);
            redirect('quiz/play-online-quiz/' . $id);
        }
        $this->data['quiz'] = $this->Quiz_model->get($id);
        if ($this->data['quiz']) {
            $this->data['title'] = 'Quiz Instruction';
            $this->data['page'] = 'quiz';
            $this->__template('quiz/instruction', $this->data);
        } else {
            redirect('quiz');
        }
    }

    /**
     * Edit quiz details
     * @param string $quiz_id
     */
    function edit($quiz_id = '') {
        $this->load->model('department/Degree_model');
        if ($quiz_id) {
            $this->data['title'] = 'Edit Quiz';
            $this->data['page'] = 'quiz';
            $this->data['department'] = $this->Degree_model->order_by_column('d_name');
            $this->data['quiz'] = $this->Quiz_model->get($quiz_id);
            $this->data['quiz_questions'] = $this->Quiz_questions_model->get_many_by(array(
                'quiz_id' => $quiz_id
            ));
            $this->__template('quiz/edit', $this->data);
        }

        //redirect('quiz');
    }

    /**
     * Update quiz
     * @param string $id
     */
    function update($id = '') {
        if ($_POST) {
            $this->Quiz_model->update($id, array(
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'department_id' => $_POST['department'],
                'branch_id' => $_POST['branch'],
                'batch_id' => $_POST['batch'],
                'semester_id' => $_POST['semester'],
                'validity_type' => $_POST['validity_type'],
                'validity_value' => $_POST['validity_value'],
                'total_marks' => $_POST['total_marks'],
                'nagative_mark_status' => $_POST['nagative_marks_status'],
                'nagative_mark' => $_POST['nagative_marks'],
                'total_questions' => $_POST['total_questions'],
                'timer_status' => $_POST['timer_status'],
                'timer_value' => $_POST['timer_value'],
                'difficulty_level' => $_POST['difficulty_level'],
                'start_date' => date('Y-m-d', strtotime($_POST['start_date'])),
                'end_date' => date('Y-m-d', strtotime($_POST['end_date'])),
                'status' => $_POST['status'],
                'result_date' => date('Y-m-d', strtotime($_POST['result_date']))
            ));
            $this->flash_notification('Quiz is successfully updated.');
        }

        redirect(base_url('quiz'));
    }

    function update_question($question_id = '') {
        if ($_POST) {
            //update question
            $this->Quiz_questions_model->update($question_id, array(
                'question' => $_POST['question'],
                'question_type' => $_POST['question_type'],
                'currect_answer' => $_POST['currect_answer']
            ));

            //question options
            $i = 1;
            $options = $this->Quiz_question_options_model->get_many_by(array(
                'quiz_question_id' => $question_id
            ));

            foreach ($options as $row) {
                $this->Quiz_question_options_model->update($row->quiz_question_option_id, array(
                    'question_option' => $_POST['option_' . $i]
                ));
                $i++;
            }
        }

        redirect(base_url('quiz'));
    }

    /**
     * Check for quiz is available to user or not
     * @param string $quiz_id
     * @param string $user_id
     * @return boolean
     */
    function is_quiz_available_to_user($quiz_id = '', $user_id = '') {
        $quiz = $this->Quiz_model->get($quiz_id);
        if ($quiz->validity_type == 'Day') {            
            //$end =  date('Y-m-d', strtotime($quiz->end_date. ' + 2 days'));
            //  && strtotime(date('Y-m-d')) >= $end
            if (strtotime(date('Y-m-d')) <= strtotime($quiz->end_date)) {
                return TRUE;
            }
        } else {
            $total_attempts = $this->Quiz_result_model->total_exam_attempts($quiz_id, $user_id);
            if ($quiz->validity_value > $total_attempts) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Delete question details
     * @param string $quiz_id
     * @param string $question_id
     */
    function delete_question($quiz_id = '', $question_id = '') {
        if ($question_id) {
            $this->Quiz_questions_model->delete($question_id);

            //update total question
            $this->update_total_question_after_delete($quiz_id);
            $this->flash_notification('Question is successfully deleted.');
        }

        redirect(base_url('quiz/edit/' . $quiz_id));
    }

    /**
     * 
     * @param type $quiz_id
     */
    function update_total_question_after_delete($quiz_id) {
        $sql = "UPDATE quiz SET total_questions = total_questions - 1 ";
        $sql .= "WHERE quiz_id = ? ";
        $sql .= "AND total_questions > 0";
        $this->db->query($sql, [$quiz_id]);
    }

    function user_quiz_history() {
        $this->data['title'] = 'Quiz History';
        $this->data['page'] = 'quiz_history';
        $this->data['quiz_history'] = $this->Quiz_model->user_quiz_history($this->session->userdata('user_id'));
        $this->__template('quiz/user_quiz_history', $this->data);
    }

    function view_history($quiz_id, $user_id) {
        $this->load->model('user/User_model');
        $this->data['title'] = 'Quiz History';
        $this->data['page'] = 'quiz_history';
        $this->data['history'] = $this->Quiz_model->view_history($quiz_id, $user_id);
        $this->data['user'] = $this->User_model->get($user_id);
        $this->data['quiz'] = $this->Quiz_model->get($quiz_id);
        $this->__template('quiz/view_history', $this->data);
    }

    function quiz_history() {
        
    }

}
