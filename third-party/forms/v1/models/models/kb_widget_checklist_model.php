<?php

/**
 * Description of bsg_widget_checklist_model
 *
 * @author kboydstu
 */
class bsg_widget_checklist_model {

  public $checklist_id = NULL;
  public $checklist_title = NULL;
  public $checklist_description = NULL;
  public $questions = array();

  public function __construct(){

  }

  public function set_checklist($values = array()){
    if(!empty($values)){
      $this->checklist_id = $values['checklist_id'];
      $this->checklist_title = $values['checklist_title'];
      $this->checklist_description = $values['checklist_description'];
    }
  }  
  
  public function set_questions($values = array(), $debug = FALSE){
    $this->questions = array();
    if(!empty($values)){
      foreach($values as $question_id => $question){
        $question['checklist_record_id'] = isset($question['checklist_record_id']) ? $question['checklist_record_id'] : 'new';
        $this->questions[$question['checklist_record_id']][$question_id] = $question;
      }
    }
  }

  public function set_question_reponse($checklist_record_id = null, $question_id = null, $response = null){
    if(!empty($this->questions) && !empty($question_id) && isset($this->questions[$checklist_record_id])){
      foreach($this->questions[$checklist_record_id] as $index => $question){
        if($question_id == $question['checklist_question_id']){
          $this->questions[$checklist_record_id][$index]['question_answer'] = isset($response['question_answer']) ? $response['question_answer'] : NULL;
          $this->questions[$checklist_record_id][$index]['question_comment'] = isset($response['question_comment']) ? bsg_clean_msword($response['question_comment']) : NULL;
          break;
        }
      }
    }
  }

  public function render($settings = array()){
    
  }
}