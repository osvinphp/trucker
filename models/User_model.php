 <?php
 defined('BASEPATH') OR exit('No direct script access allowed');
 class User_model extends CI_Model{
	public function __construct() {
		parent::__construct();
		$this->load->database();
		$this->load->helper('url');

	}

		
	 	public function log_out($unique_deviceId,$user_id){

	 			$data = array('status' => '0');
	 			$this->db->where('unique_device_id',$unique_deviceId);
	 			$this->db->where('user_id',$user_id);
	 			$qu= $this->db->update('trucker_login',$data);
	 			return 1;
	 	}
	 	public function getfavshipper($user_id){
	 		$res=$this->db->query("SELECT * from trucker_usersfavshipper left join trucker_truckerdetail on trucker_truckerdetail.trucker_id=trucker_usersfavshipper.trucker_id where user_id='".$user_id."'  ")->result();
	 		print_r($this->db->last_query());die;

	 	}



		public function insert_data($tbl_name,$data)                                         /* Data insert */
	    {
	      	$this->db->insert($tbl_name, $data);
	       	$insert_id = $this->db->insert_id();
	        return $insert_id;

	    }

	    public function update_data($tbl_name,$data,$where){                                 /* Update data */

	      $this->db->where($where);
	      $this->db->update($tbl_name,$data);

	     return($this->db->affected_rows())?1:0;
	    }

	    public function select_data($selection,$tbl_name,$where=null,$order=null)                   /* Select data with condition*/
		    {
		      if (empty($where)&&empty($order)) {
		      $data_response = $this->db->select($selection)
		           ->from($tbl_name)
		           ->get()->result();
		    }
		    elseif(empty($order)){
		    $data_response =
		    $this->db->select($selection)
		           ->from($tbl_name)
		           ->where($where)
		           ->get()->result();

		    }else{
		    $data_response =
		    $this->db->select($selection)
		           ->from($tbl_name)
		           ->where($where)
		           ->order_by($order)
		           ->get()->result();
		    }
	    return $data_response;

	    }

		/*push notification for android common function*/
    public function androidPush($pushData=null){
    	// print_r($pushData);
	    $mytime = date("Y-m-d H:i:s");
	    // if($pushData['Utype'] == 2){
	    // $api_key = "AAAAhyf2Jug:APA91bHP9_oA8arOG3aUVBAt9tqjaGUvr3Od4G7XZAFxsvfMCyVf31YB21f0cy_dwz-vFuGp9a1jV8rfMEQty8OQo5we71epg2v9m-QtS9jvNz_fMUO2vz1_6qE1gtuV17e8Ouir_wMV"; //for driver app
	    // }else if($pushData['Utype'] == 1){
	     $api_key = "AAAANVzzBLc:APA91bHbiNHUFrqMisFYeCXmk11hnwNCG9WfzuiRoHGiiQD0wL9Quv4SlEaNgd6pQdqObnL7eetzJ5MSk1Pq0agkPwiOh_J5M9sxcS9HlchG5g90yxcIh4-AGXIbCTYiZ0vg6bSKu1wR";  //for user app
	    // $api_key="";
	    // }
	    $fcm_url = 'https://fcm.googleapis.com/fcm/send';
	 	$fields = array(
      		'registration_ids' => array(
        	$pushData['token']
      		) ,
	     	 'data' => array(
	      	  "message" =>$pushData['message'] ,
	      	  "action" => $pushData['action'],
	      	  'booking_id' => $pushData['booking_id'],
	      	  'profile_pic' => $pushData['profile_pic'],
	      	  "avgrating" => $pushData['avgrating'],
	      	  "vehicleName" => $pushData['vehicleName'],
	      	  'vehicleNumber' => $pushData['vehicleNumber'],
	       	  "time" => $mytime
	      ) ,
	    );
	    $headers = array(
	      'Authorization: key=' . $api_key,
	      'Content-Type: application/json'
	    );
	    $curl_handle = curl_init();
	    // set CURL options
	    curl_setopt($curl_handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	    curl_setopt($curl_handle, CURLOPT_URL, $fcm_url);
	    curl_setopt($curl_handle, CURLOPT_POST, true);
	    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, json_encode($fields));
	    $response = curl_exec($curl_handle);
	    // print_r($response);die();
	    curl_close($curl_handle);
  	}
	/*push notification for ios common function*/
  	public function iosPush($pushData=null) {
    // print_r($pushData);
    $deviceToken = $pushData['token'];
    $passphrase = '';
    $ctx = stream_context_create();
    if($pushData['Utype'] == 1){
    stream_context_set_option($ctx, 'ssl', 'local_cert', './certs/MoversPushDevelpoment.p12');
    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
    }else if($pushData['Utype'] == 2){

    stream_context_set_option($ctx, 'ssl', 'local_cert', './certs/MoversPushDevelpoment.p12');
    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
    }
    // Open a connection to the APNS server

    $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
   // if (!$fp) exit("Failed to connect: $err $errstr" . PHP_EOL);
  
    	 $body['aps'] = array(
        "message" =>$pushData['message'] ,
        "action" => $pushData['action'],
        'booking_id' => $pushData['booking_id'],
        'sound' => 'default'
    );

   
    
	}



}
