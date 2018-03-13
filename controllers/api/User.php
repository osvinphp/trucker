<?php


defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';
/**
* This is an example of a few basic user interaction methods you could use
* all done with a hardcoded array
*
* @package         CodeIgniter
* @subpackage      Rest Server
* @category        Controller
* @author          Phil Sturgeon, Chris Kacerguis
* @license         MIT
* @link            https://github.com/chriskacerguis/codeigniter-restserver
*/
class User extends REST_Controller {

	function __construct()
	{
     // Construct the parent class
    parent::__construct();
    $this->load->database();
    $this->load->model('User_model');
    $this->load->helper('url');
    $this->load->helper('form');
    $this->load->library('twilio');

    $config = Array(

      'protocol' => 'sendmail',
      'mailtype' => 'html',
      'charset' => 'utf-8',
      'wordwrap' => TRUE

      );
 // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
   $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
   $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
   $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
 }


public function basicdetail_post(){
  $data=array(
    'name'=>$this->input->post('name'),
    'email'=>$this->input->post('email'),
    'phone'=>$this->input->post('phone'),
    'country_code'=>$this->input->post('country_code'),
    'password'=>md5($this->input->post('password')),
    );

  $image='profile_pic';
  $upload_path='public/api';
  $imagename=$this->file_upload($upload_path,$image);
  $data['profile_pic']=$imagename;

  $loginParams =  array(
    'device_id'=>$this->input->post('device_id'),
    'unique_device_id '=>$this->input->post('unique_device_id'),
    'token_id'=>$this->input->post('token_id'),
    'status'=>1,
    'date_created' =>date('Y-m-d H:i:s')
  );


  $data1 = $this->User_model->select_data('*','trucker_users',array('phone'=>$data['phone']));
  if (!empty($data1)) {
    $loginParams['user_id']=$data1[0]->id;
    $result=$this->User_model->insert_data('trucker_login',$loginParams);
    $loginsdata= $this->User_model->update_data('trucker_users',$data,array('phone'=>$data['phone']));
      $data2 = $this->User_model->select_data('*','trucker_users',array('phone'=>$data['phone']));
  }
  else{
    $result=$this->User_model->insert_data('trucker_users',$data);
    $loginParams['user_id']=$result;
    $loginsdata=$this->User_model->insert_data('trucker_login',$loginParams);
    $data2 = $this->User_model->select_data('*','trucker_users',array('id'=>$result));
  }
  if ($data2) {
      $result = array(
      "controller" => "User",
      "action" => "basicdetail",
      "ResponseCode" => true,
      "MessageWhatHappen" =>"sucessfully registered",
      "signUpResponse" => $data2
      );
  }
  else{
    $result = array(
    "controller" => "User",
    "action" => "signup",
    "ResponseCode" => false,
    "MessageWhatHappen" => "Something went wrong",
    );
  }
 $this->set_response($result, REST_Controller::HTTP_OK);

}


public function sendotp_post(){
  $this->load->library('twilio');
  $phone=$this->input->post('phone');
  $country_code=$this->input->post('country_code');

    $from = '14065347696';
    $to = $country_code.$phone;
    $otp=rand(1000,9999);
    $message = 'Your otp is '.$otp ;
    $response = $this->twilio->sms($from, $to, $message);


    if($response->IsError){
      $result = array(
      "controller" => "User",
      "action" => "signup",
      "ResponseCode" => false,
      "MessageWhatHappen" => "Something went wrong",
      );
   
    }
      
    else{
      $res = $this->User_model->select_data('*','trucker_otp',array('phone'=>$phone));
      $data=array('phone'=>$phone,'otp'=>$otp,'country_code'=>$country_code);
      if (empty($res)) {
        $result=$this->User_model->insert_data('trucker_otp',$data);
      }
      else{
        $result= $this->User_model->update_data('trucker_otp',$data,array('phone'=>$phone));
      }
      $result = array(
      "controller" => "User",
      "action" => "sendotp",
      "ResponseCode" => true,
      "MessageWhatHappen" =>"sucessfully send otp.",
      "otp"=>$otp
      );
    }
 $this->set_response($result, REST_Controller::HTTP_OK);
}

public function verifyotp_post(){
  $country_code=$this->input->post('country_code');
  $phone=$this->input->post('phone');
  $otp=$this->input->post('otp');
  $data = $this->User_model->select_data('*','trucker_otp',array('phone'=>$phone,'otp'=>$otp));
  if (!empty($data)) {

    $res=$this->db->query("SELECT * from trucker_users where phone ='".$phone."' ")->row();
       $result = array(
      "controller" => "User",
      "action" => "verfiyotp",
      "ResponseCode" => true,
      "MessageWhatHappen" =>"sucessfully verified.",
      "response"=>$res
      );
  }
  else{
     $result = array(
      "controller" => "User",
      "action" => "verfiyotp",
      "ResponseCode" => false,
      "MessageWhatHappen" => "Invalid Otp.",
      );

  }
  $this->set_response($result, REST_Controller::HTTP_OK);
}
  public function updatetruckerdetail_post(){
    $trucker_id=$this->input->post('trucker_id');
    $data=array(
    'price'=>$this->input->post('price'),
    'licence_no'=>$this->input->post('licence_no'),
    'capacity'=>$this->input->post('capacity'),
    'volume'=>$this->input->post('volume'),
    'dimension'=>$this->input->post('dimension'),
    'maked_by'=>$this->input->post('Maked_by')
    );


  $image='truck_image';
  $upload_path='public/api';
  $imagename=$this->file_upload($upload_path,$image);
  $data['truck_image']=$imagename;

  $res = $this->User_model->select_data('*','trucker_truckerdetail',array('trucker_id'=>$trucker_id));
  if (empty($res)) {
      $data['trucker_id']=$trucker_id;
    $result=$this->User_model->insert_data('trucker_truckerdetail',$data);
  }
  else{
    $result= $this->User_model->update_data('trucker_truckerdetail',$data,array('trucker_id'=>$trucker_id));
  }


  if ($result) {
      $data = $this->User_model->select_data('*','trucker_truckerdetail',array('trucker_id'=>$trucker_id));
      $result = array(
      "controller" => "User",
      "action" => "updatetruckerdetail",
      "ResponseCode" => true,
      "MessageWhatHappen" =>"sucessfully added",
      "signUpResponse" => $data
      );
  }
  else{
    $result = array(
    "controller" => "User",
    "action" => "updatetruckerdetail",
    "ResponseCode" => false,
    "MessageWhatHappen" => "Something went wrong",
    );
  }
   $this->set_response($result, REST_Controller::HTTP_OK);
  }


    public function getprofile_post(){

      $user_id=$this->input->post('user_id');
      $type=$this->input->post('type');
      if ($type==1) {
        $data=$this->db->query("SELECT * from trucker_users where id='".$user_id."'")->result();
      }
      elseif($type==2){
        $data=$this->db->query("SELECT * from trucker_users join trucker_truckerdetail on trucker_truckerdetail.trucker_id=trucker_users.id  where trucker_users.id='".$user_id."'")->result();
      }
        if ($data) {
        $result = array(
        "controller" => "User",
        "action" => "getprofile",
        "ResponseCode" => true,
        "MessageWhatHappen" =>"your data shows sucessfully",
        "signUpResponse" => $data
        );
        }
        else{
        $result = array(
        "controller" => "User",
        "action" => "getprofile",
        "ResponseCode" => false,
        "MessageWhatHappen" => "Something went wrong",
        );
      }
      $this->set_response($result, REST_Controller::HTTP_OK);
  }


public function logout_post(){
 $user_id=$this->input->post('user_id');
 $unique_deviceId=$this->input->post('unique_device_id');
 $log_out= $this->User_model->log_out($unique_deviceId,$user_id);
 if (($log_out)==1){
   $result = array(
     "controller" => "User",
     "action" => "logout",
     "ResponseCode" => true,
     "MessageWhatHappen" =>"sucessfully logged out",
     );
 }
 else{
   $result = array(
     "controller" => "User",
     "action" => "logout",
     "ResponseCode" => false,
     "MessageWhatHappen" => "Something went wrong"
     );
 }
 $this->set_response($result, REST_Controller::HTTP_OK);
}
public function pushnotification_post(){
  $user_id=$this->input->post('user_id');
  $status=$this->input->post('status');

  $var= $this->User_model->update_data('trucker_users',array('push_notification'=>$status),array('id'=>$user_id));
  $result = array(
      "controller" => "User",
      "action" => "pushnotification",
      "ResponseCode" => true,
      "MessageWhatHappen" =>"Your status updated sucessfully",
      );
  $this->set_response($result,REST_Controller::HTTP_OK);

}
public function updateprofile_post(){
  $arra = array(
    'name'=>$this->input->post('name'),
    'phone'=>$this->input->post('phone'),
    'email'=>$this->input->post('email')
    );
  $user_id=$this->input->post('user_id');


  /*updation of profile pic start*/
  $image='profile_pic';
  $upload_path='public/api';
  $imagename=$this->file_upload($upload_path,$image);
  $arra['profile_pic']=$imagename;
  /*updation of profile pic end*/

  $data=array_filter($arra);
  // $result=$this->db->query("SELECT * from trucker_users where phone ='".$data['phone']."' and id!='".$user_id."' ")->result();
  // if ($result) {
  //   $result = array(
  //     "controller" => "User",
  //     "action" => "updateprofile",
  //     "ResponseCode" => false,
  //     "MessageWhatHappen" =>"Phone number already exists.",
  //     );    
  // }
  // else{
    $var= $this->User_model->update_data('trucker_users',$data,array('id'=>$user_id));
    $getRes = $this->User_model->select_data('*','trucker_users',array('id'=>$user_id));
    $result = array(
      "controller" => "User",
      "action" => "updateprofile",
      "ResponseCode" => true,
      "MessageWhatHappen" =>"Your data updated sucessfully",
      "response"=>$getRes
      );
  // }
  $this->set_response($result,REST_Controller::HTTP_OK);
}


public function addbooking_post(){
  $data = array(
    'user_id'=>$this->input->post('user_id'),
    'pickup_loc'=>$this->input->post('pickup_loc'),
    'destination_loc'=>$this->input->post('destination_loc'),
    'pickup_lat'=>$this->input->post('pickup_lat'),
    'pickup_lng'=>$this->input->post('pickup_lng'),
    'destination_lat'=>$this->input->post('destination_lat'),
    'destination_lng'=>$this->input->post('destination_lng'),
    'weight'=>$this->input->post('weight'),
    'volume'=>$this->input->post('volume'),
    'freight_type'=>$this->input->post('freight_type'),
    'note'=>$this->input->post('note'),
    'truck_type'=>$this->input->post('truck_type'),
    );

  $user_id=$this->input->post('user_id');
  $booking_type=$this->input->post('booking_type');



  if ($booking_type==1) {
    $booking_date=date('Y-m-d');
    $booking_time=date('H:i:s');
  }
  else{
    $booking_date=$this->input->post('booking_date');
    $booking_time=$this->input->post('booking_time');
  }

  // $result1=$this->User_model->insert_data('trucker_booking',$data);

  $prefserver=$this->User_model->getfavshipper($user_id);
  print_r($prefserver);die;
  if ($result1) {
    $result = array(
        "controller" => "User",
        "action" => "addbooking",
        "ResponseCode" => true,
        "MessageWhatHappen" =>"Your booking has been submitted sucessfully."
        );
  }
  else{
    $result = array(
        "controller" => "User",
        "action" => "addbooking",
        "ResponseCode" => false,
        "MessageWhatHappen" =>"Something went wrong."
        );
  }
  $this->set_response($result,REST_Controller::HTTP_OK);
}

public function bookinghistory_post(){
  $user_id=$this->input->post('user_id');
  $type=$this->input->post('type');
  $booking_type=$this->input->post('booking_type');
  /*type 1 for user pal*/
  if ($type==1) {
    if ($booking_type==1) {
      $getRes = $this->User_model->select_data('*','trucker_booking',array('user_id'=>$user_id,'is_accepted'=>1,'is_started'=>1,'is_completed'=>1,'is_cancelled'=>0));
    }
    else{
      $getRes = $this->User_model->select_data('*','trucker_booking',array('user_id'=>$user_id,'is_accepted'=>1,'is_started'=>0,'is_completed'=>0,'is_cancelled'=>0));
    }
  }
  /*type 2 for trucker pal*/
  else{
    $getRes = $this->User_model->select_data('*','trucker_booking',array('trucker_id'=>$user_id));
  }
  if (!empty($getRes)) {
        $result = array(
      "controller" => "User",
      "action" => "bookinghistory",
      "ResponseCode" => true,
      "MessageWhatHappen" =>"Your data shows sucessfully",
      "response"=>$getRes
      );
  }
  else{
      $result = array(
      "controller" => "User",
      "action" => "bookinghistory",
      "ResponseCode" => false,
      "MessageWhatHappen" =>"No data exists in table.",
      );
  }
  $this->set_response($result,REST_Controller::HTTP_OK);
}


public function bookingdetail_post(){
  $booking_id=$this->input->post('booking_id');
  $getRes = $this->User_model->select_data('*','trucker_booking',array('id'=>$booking_id));
  if (!empty($getRes)) {
        $result = array(
      "controller" => "User",
      "action" => "bookingdetail",
      "ResponseCode" => true,
      "MessageWhatHappen" =>"Your data shows sucessfully",
      "response"=>$getRes
      );
  }
  else{
      $result = array(
      "controller" => "User",
      "action" => "bookingdetail",
      "ResponseCode" => false,
      "MessageWhatHappen" =>"No data exists in table.",
      );
  }
  $this->set_response($result,REST_Controller::HTTP_OK);
} 

public function lookuponline_post(){
  $trucker_id=$this->input->post('trucker_id');
  $data=array('status'=>$this->input->post('status'));
  $var= $this->User_model->update_data('trucker_truckerdetail',$data,array('trucker_id'=>$trucker_id));
  $result = array(
      "controller" => "User",
      "action" => "lookuponline",
      "ResponseCode" => true,
      "MessageWhatHappen" =>"Your status updated sucessfully"
      );
  $this->set_response($result,REST_Controller::HTTP_OK);

}
public function notificationlisting_post(){
  $user_id=$this->input->post('user_id');
  $getRes = $this->User_model->select_data('*','trucker_notification',array('user_id'=>$user_id));
  if (!empty($getRes)) {
        $result = array(
      "controller" => "User",
      "action" => "notificationlisting",
      "ResponseCode" => true,
      "MessageWhatHappen" =>"Your data shows sucessfully",
      "response"=>$getRes
      );
  }
  else{
      $result = array(
      "controller" => "User",
      "action" => "notificationlisting",
      "ResponseCode" => false,
      "MessageWhatHappen" =>"No data exists in table.",
      );
  }
  $this->set_response($result,REST_Controller::HTTP_OK);

}


public function addshiper_post(){
  $data=array('user_id'=>$this->input->post('user_id'),
    'trucker_id'=>$this->input->post('trucker_id')
    );
  $result1=$this->User_model->insert_data('trucker_usersfavshipper',$data);
  if ($result1) {
    $result = array(
        "controller" => "User",
        "action" => "addshiper",
        "ResponseCode" => true,
        "MessageWhatHappen" =>"Your shipper has been added sucessfully."
        );
  }
  else{
    $result = array(
        "controller" => "User",
        "action" => "addshiper",
        "ResponseCode" => false,
        "MessageWhatHappen" =>"Something went wrong."
        );
  }
  $this->set_response($result,REST_Controller::HTTP_OK);


}

public function myshippers_post(){
  $user_id=$this->input->post('user_id');
  $getRes = $this->User_model->select_data('*','trucker_usersfavshipper',array('user_id'=>$user_id));
  if (!empty($getRes)) {
        $result = array(
      "controller" => "User",
      "action" => "myshippers",
      "ResponseCode" => true,
      "MessageWhatHappen" =>"Your data shows sucessfully",
      "response"=>$getRes
      );
  }
  else{
      $result = array(
      "controller" => "User",
      "action" => "myshippers",
      "ResponseCode" => false,
      "MessageWhatHappen" =>"No data exists in table.",
      );
  }
  $this->set_response($result,REST_Controller::HTTP_OK);
}


public function deleteshipper_post(){
  $ship_id=$this->input->post('ship_id');
  $this->db->where('id', $ship_id);
  $getRes=$this->db->delete('trucker_usersfavshipper'); 
  if (!empty($getRes)) {
        $result = array(
      "controller" => "User",
      "action" => "deleteshipper",
      "ResponseCode" => true,
      "MessageWhatHappen" =>"Your data deleted sucessfully"
      );
  }
  else{
      $result = array(
      "controller" => "User",
      "action" => "deleteshipper",
      "ResponseCode" => false,
      "MessageWhatHappen" =>"Something went wrong.",
      );
  }
  $this->set_response($result,REST_Controller::HTTP_OK);
}

public function addrating_post(){
  $data=array(
    'user_id'=>$this->input->post('user_id'),
    'trucker_id'=>$this->input->post('trucker_id'),
    'booking_id'=>$this->input->post('booking_id'),
    'rating'=>$this->input->post('rating'),
    'comment'=>$this->input->post('comment')
    );
    $result1=$this->User_model->insert_data('trucker_rating',$data);
  if ($result1) {
    $result = array(
        "controller" => "User",
        "action" => "addrating",
        "ResponseCode" => true,
        "MessageWhatHappen" =>"Your rating has been added sucessfully."
        );
  }
  else{
    $result = array(
        "controller" => "User",
        "action" => "addrating",
        "ResponseCode" => false,
        "MessageWhatHappen" =>"Something went wrong."
        );
  }
  $this->set_response($result,REST_Controller::HTTP_OK);


}


public function  bookingaction_post(){
  $booking_id=$this->input->post('booking_id');
  $trucker_id=$this->input->post('trucker_id');
  $type=$this->input->post('type');
  $getRes = $this->User_model->select_data('*','trucker_booking',array('id'=>$booking_id));
  if ($type==1) {
    if ($getRes[0]->is_accepted==0 && $getRes[0]->is_started==0 && $getRes[0]->is_completed==0 &&  $getRes[0]->is_cancelled==0  ) {
      $data=array(
        'trucker_id'=>$trucker_id,
        'is_accepted'=>1
        );
      $var= $this->User_model->update_data('trucker_booking',$data,array('id'=>$booking_id));
      $msg= 1;/*for succesfully accepted*/
    }
    else{
      $msg= 2;/*for acception failed*/
    }

  }
  elseif ($type==2) {
    if ($getRes[0]->is_accepted==1 && $getRes[0]->is_started==0 && $getRes[0]->is_completed==0 &&  $getRes[0]->is_cancelled==0  ) {
      $data=array(
        'is_started'=>1
        );
      $var= $this->User_model->update_data('trucker_booking',$data,array('id'=>$booking_id));
      $msg= 3;/*for succesfully started*/
    }
    else{
      $msg= 4;/*for started failed*/
    }
  }
  elseif ($type==3) {
    if ($getRes[0]->is_accepted==1 && $getRes[0]->is_started==1 && $getRes[0]->is_completed==0 &&  $getRes[0]->is_cancelled==0  ) {
      $data=array(
        'is_completed'=>1
        );
      $var= $this->User_model->update_data('trucker_booking',$data,array('id'=>$booking_id));
      $msg= 5;/*for succesfully completed*/
    }
    else{
      $msg= 6;/*for completion failed*/
    }
  }


  elseif($type==4){
    if ($getRes[0]->is_accepted==1 &&   $getRes[0]->is_cancelled==0  ) {
        $data=array(
        'is_cancelled'=>1
        );
      $var= $this->User_model->update_data('trucker_booking',$data,array('id'=>$booking_id));
      $msg=7;
    }
    else{
      $msg= 8;/*for acception failed*/
    }
  }
  elseif($type==5){
    $data=array(
    'trucker_id'=>$this->input->post('trucker_id'),
    'booking_id'=>$this->input->post('booking_id'),
    );
    $result1=$this->User_model->insert_data('trucker_declinedbooking',$data);
    if ($result1) {
      $msg=9;/*booking declined succesfully*/
    }
    else{
      $msg=10;  /*something went wrong*/

    }
  }
  if ($msg==1) {
        $result = array(
        "controller" => "User",
        "action" => "bookingaction",
        "ResponseCode" => true,
        "MessageWhatHappen" =>"Your booking has accepted succesfully."
        );
  }
  elseif($msg==2){
      $result = array(
        "controller" => "User",
        "action" => "bookingaction",
        "ResponseCode" => false,
        "MessageWhatHappen" =>"Your booking  accepted failed."
        );

  }
  elseif($msg==3){
   $result = array(
        "controller" => "User",
        "action" => "bookingaction",
        "ResponseCode" => true,
        "MessageWhatHappen" =>"Your booking started succesfully."
        );
    
  }
  elseif($msg==4){
   $result = array(
        "controller" => "User",
        "action" => "bookingaction",
        "ResponseCode" => false,
        "MessageWhatHappen" =>"Your booking  start failed."
        );
  }
    elseif($msg==5){
   $result = array(
        "controller" => "User",
        "action" => "bookingaction",
        "ResponseCode" => true,
        "MessageWhatHappen" =>"Your booking  compeleted succesfully."
        );
  }
  elseif($msg==6){
   $result = array(
        "controller" => "User",
        "action" => "bookingaction",
        "ResponseCode" => false,
        "MessageWhatHappen" =>"Your booking  compeleted failed."
        );
  }
  elseif($msg==7){
   $result = array(
        "controller" => "User",
        "action" => "bookingaction",
        "ResponseCode" => true,
        "MessageWhatHappen" =>"Your booking  cancelled succesfully."
        );
  }
  elseif($msg==8){
   $result = array(
        "controller" => "User",
        "action" => "bookingaction",
        "ResponseCode" => false,
        "MessageWhatHappen" =>"Your booking  cancelled failed."
        );
  }
    elseif($msg==9){
   $result = array(
        "controller" => "User",
        "action" => "bookingaction",
        "ResponseCode" => true,
        "MessageWhatHappen" =>"Your booking  declined succesfully."
        );
  }
  elseif($msg==10){
   $result = array(
        "controller" => "User",
        "action" => "bookingaction",
        "ResponseCode" => false,
        "MessageWhatHappen" =>"Something went wrong."
        );
  }
  $this->set_response($result,REST_Controller::HTTP_OK);
}


public function homeview_post(){
  $user_id=$this->input->post('user_id');
  $type=$this->input->post('type');
  /*type 1 for customer */
  if ($type==1) {
    $getRes['booking_data'] = $this->db->query("SELECT * from trucker_booking where user_id='".$user_id."' and is_accepted=1 ")->result();
  }
  /*type 2 for trucker*/
  elseif($type==2){
    $getRes['booking_data'] = $this->db->query("SELECT * from trucker_booking where trucker_id='".$user_id."' and is_accepted=1 ")->result();
  }
  if (!empty($getRes['booking_data'])) {
        $result = array(
      "controller" => "User",
      "action" => "bookinghistory",
      "ResponseCode" => true,
      "MessageWhatHappen" =>"Your data shows sucessfully",
      "response"=>$getRes
      );
  }
  else{
      $result = array(
      "controller" => "User",
      "action" => "bookinghistory",
      "ResponseCode" => false,
      "MessageWhatHappen" =>"No data exists in table.",
      );
  }
  $this->set_response($result,REST_Controller::HTTP_OK);
} 

public function file_upload($upload_path, $image) {

  $baseurl = base_url();
  $config['upload_path'] = $upload_path;
  $config['allowed_types'] = 'gif|jpg|png|jpeg';
  $config['max_size'] = '0';
  $config['max_width'] = '0';
  $config['max_height'] = '0';
  $config['overwrite'] = FALSE;

  $this->load->library('upload', $config);
  if (!$this->upload->do_upload($image))
  {
    $error = array(
      'error' => $this->upload->display_errors()
      );
    // print_r($error); die;
    return $imagename = "";
  }
  else
  {
    $detail = $this->upload->data();
    return $imagename = $baseurl . $upload_path .'/'. $detail['file_name'];
  }
}
}