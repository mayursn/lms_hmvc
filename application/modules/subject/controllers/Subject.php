<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Subject extends MY_Controller {

    /**
     * Constructor
     * 
     * @return void
     */
    function __construct() {
        parent::__construct();
        $this->load->model('subject/Subject_manager_model');
    }

    function index() {
        if($this->session->userdata('professor_id'))
        {
            redirect(base_url('subject/professor_subject'));
        }
        $this->data['title'] = 'Subject';
        $this->data['page'] = 'subject';
        $this->data['subject'] = $this->Subject_manager_model->order_by_column('subject_name');
       
        $this->__template('subject/index', $this->data);
    }
    
    function subject_detail($id)
    {
           Modules::run('MY_Controller');
        $this->load->helper('role_permission_helper');
        $this->load->model('user/User_model');

        $this->data['title'] = 'Subject Detail';
        $this->data['page'] = 'subject detail';
        $this->data['param'] = $id;           
        $this->data['subjectdetail']= $this->Subject_manager_model->subjectdetail($id);

	$wherearray=array("sm_id" => $id);
        $this->data['subject']=$this->Subject_manager_model->get_by($wherearray);

        $this->data['user']=$this->User_model->get_all();
        $this->__template('subject/subjectdetail/index', $this->data);
    }
    function create() {
        if ($_POST) {
            
            $data['subject_name'] = $this->input->post('subname');
            $data['subject_code'] = $this->input->post('subcode');
            $data['sm_status'] = $this->input->post('status');
            $this->Subject_manager_model->insert($data);
            $this->flash_notification('Subject is successfully added.');
        }

        redirect(base_url('subject'));
    }
    
    function checksubject($param1="") {
        if($param1=='edit')
        {
            $subname = $this->input->post('subname');
            $subcode = $this->input->post('subcode');
            $wherearray=array("subject_name" => $subname, "subject_code" => $subcode);
           // $data = $this->db->get_where('subject_manager', array("subject_name" => $subname, "subject_code" => $subcode));
            $this->db->where_not_in('sm_id', $this->input->post('editid'));
            $data=$this->Subject_manager_model->get_by($wherearray);
            if (count($data) > 0) {
                echo "false";
            } else {
                echo "true";
            }
        }
        else
        {
            $subname = $this->input->post('subname');
            $subcode = $this->input->post('subcode');
            $wherearray=array("subject_name" => $subname, "subject_code" => $subcode);
            $data=$this->Subject_manager_model->get_by($wherearray);  
            //$data = $this->db->get_where('subject_manager', array("subject_name" => $subname, "subject_code" => $subcode));
           if (count($data) > 0) {
                echo "false";
            } else {
                echo "true";
            } 
        }
       
    }
    
     function checksubjects($param1="") {
        if($param1=="edit")
        {
            $subjectid = $this->input->post('subjectid');
            $degree = $this->input->post('degree');
            $course = $this->input->post('course');
            $this->db->where_not_in('sa_id', $this->input->post('editid'));
            $data = $this->db->get_where('subject_association', array("degree_id" => $degree, "course_id" => $course, "sm_id" => $subjectid));
             if ($data->num_rows() > 0) {
                    echo "false";
                } else {
                    echo "true";
                }
        }
        else
        {
            $subjectid = $this->input->post('subjectid');
            $degree = $this->input->post('degree');
            $course = $this->input->post('course');
            $data = $this->db->get_where('subject_association', array("degree_id" => $degree, "course_id" => $course, "sm_id" => $subjectid));
             if ($data->num_rows() > 0) {
                    echo "false";
                } else {
                    echo "true";
                }
        }
        
    }
    
    function update($id = '') {
        if ($_POST) {
            
            $data['subject_name'] = $this->input->post('subname');
            $data['subject_code'] = $this->input->post('subcode');
            $this->Subject_manager_model->update($id, $data);
            $this->flash_notification('Subject is successfully updated.');
        }

        redirect(base_url('subject'));
    }
    
    function subject_detail_create($param1="")
    {
         if ($_POST) {
            $data['degree_id'] = $this->input->post('degree');
            $data['course_id'] = $this->input->post('course');
            $data['sem_id'] = $this->input->post('semester');
            $data['sm_id'] = $param1;
            $data['professor_id'] = implode(',', $this->input->post('professor'));
            $data['created_date'] = date('Y-m-d');
            $this->Subject_manager_model->subject_detail_create($data);
            $this->flash_notification('Subject detail is successfully added.');
        }
        redirect(base_url('subject/subject_detail/'.$param1));
    }
    
    function subject_detail_update($param1="",$param2="")
    {
         if ($_POST) {            
            $data['degree_id'] = $this->input->post('degree');
            $data['course_id'] = $this->input->post('course');
            $data['sem_id'] = $this->input->post('semester');
            $data['professor_id'] = implode(',', $this->input->post('professor'));  
            $this->Subject_manager_model->subject_detail_update($param1,$data);
            $this->flash_notification('Subject detail is successfully updated.');
         }
        redirect(base_url('subject/subject_detail/'.$this->input->post('smid')));
    }
    function subject_detail_delete($param1="",$param2="")
    {
        $this->Subject_manager_model->subject_detail_delete($param1);
        $this->flash_notification('Subject detail is successfully deleted.');
        redirect(base_url() . 'subject/subject_detail/'.$param2);
    }
      
    function delete($id) {
        $this->Subject_manager_model->delete($id);
        $this->flash_notification('Subject is successfully deleted.');
        redirect(base_url('subject'));
    }
    
     function branch_subject($branch_id) {
        $subjects = $this->Subject_manager_model->branch_subject($branch_id);
        echo json_encode($subjects);
    }
    
    function professor_subject()
    {
        $this->load->model('branch/Course_model');
        $this->load->model('semester/Semester_model');
        
        $this->db->select('sa.*,sm.*,d.d_name,c.c_name,s.s_name');
        $this->db->where("FIND_IN_SET('".$this->session->userdata('user_id')."',sa.professor_id) !=",0);
        $this->db->from('subject_association sa');
        $this->db->join('subject_manager sm','sm.sm_id=sa.sm_id');
        $this->db->join('degree d','d.d_id=sa.degree_id');
        $this->db->join('course c','c.course_id=sa.course_id');
        $this->db->join('semester s','s.s_id=sa.sem_id');
        $this->data['subject']= $this->db->get()->result();
        
        $this->data['course'] = $this->Course_model->get_all();
        $this->data['semester'] = $this->Semester_model->get_all();
        
        $this->data['title'] = 'Subject';
        $this->data['page'] = 'subject';
        $this->__template('subject/professor_subject', $this->data);
    }
    
    function subject_department_branch_sem($dept,$branch,$sem)
    {
        $this->db->join("subject_manager s","s.sm_id = sa.sm_id");
        $this->db->where("sa.degree_id",$dept);
        $this->db->where("sa.course_id",$branch);
        $this->db->where("sa.sem_id",$sem);
        $res = $this->db->get_where("subject_association sa")->result();
        echo json_encode($res);
    }
}
