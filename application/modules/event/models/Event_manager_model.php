<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Event_manager_model extends MY_Model {
    
    protected $primary_key = 'event_id';
    
    public $before_create = array('timestamps');
    public $before_update = array('update_timestamps');
      
    /**
     * Set timestamp field
     * @param array $degree
     * @return array
     */
    protected function timestamps($event) {
         if(check_role_approval())
        {
            $event['status'] = 0;
        }
        
        $event['created_date'] = $event['updated_date'] = date('Y-m-d H:i:s');
        
        return $event;
    }
    protected function update_timestamps($event)
    {
        if(check_role_approval())
        {
            $event['status'] = 0;
        }
        $event['updated_date'] = date('Y-m-d H:i:s');
        return $event;
    }
}