<?php
App::uses('Component', 'Controller');
App::uses('HttpSocket', 'Network/Http');
App::uses('HttpResponse', 'Network/Http');
App::uses('CakeTime', 'Utility');

class GoogleCalendarComponent extends Component {

	public $components = array('Session');

	public $id;
	public $secret;
	public $uri = 'https://accounts.google.com/o/oauth2/auth';
	public $calendarScope = 'https://www.googleapis.com/auth/calendar';
	public $tokenUri = 'https://www.googleapis.com/oauth2/v3/token';
	public $controller;
	public $config = array();
	public $authCode;
	public $returnData=array();
	public $googleAccountID;
	public $state;
	public $timezone = 'Europe/Rome';
	public $eventMap = array(
		'end'=>'end',
		'start'=>'start',
		'summary'=>'title',
		'description'=>'description',
		'location'=>'address'
	);
	public $returning;



	public function initialize(Controller $controller) {
//         debug(&$controller);
			date_default_timezone_set ($this->timezone);
    }

	function startup(Controller $controller){
		$this->controller = $controller;
// 		debug($controller->name);
	}

	public function authorize($state = null)
	{
		$apiCall = $this->uri;
		$parameters = array(
			'redirect_uri'=>rawurlencode(FULL_BASE_URL.'/google/auth/'),
			'response_type'=>'code',
			'client_id'=>$this->id,
			'scope'=>rawurlencode('email profile '.$this->calendarScope),
			'approval_prompt'=>'force',
			'access_type'=>'offline'
		);
		if($state)
			$parameters['state'] = $state;
		$apiCall = $apiCall.'?';
		foreach($parameters as $p=>$v)
			$apiCall = $apiCall.$p.'='.$v.'&';
		$apiCall = substr($apiCall,0,-1);
		$this->state = $state;
		$this->controller->redirect($apiCall);
	}
	
	public function eventMapping($event)
	{
		App::uses('CakeTime', 'Utility');
		$new_event = array();
		foreach($this->eventMap as $key=>$value)
		{
			if(isset($event[$value]) && !empty($event[$value]))
			{
				if(in_array($key,array('start','end')))
				{
					$new_event[$key] = array(
						'dateTime'=>CakeTime::format($event[$value],"%Y-%m-%dT%H:%M:%SZ"),
						'timeZone'=>$this->timezone
					);				
				}
				else
					$new_event[$key] = $event[$value];
			}
		}
		return $new_event;
	}
	
	public function readToken()
	{
		$token = $this->Session->read('GoogleCalendar.token');
		if($token)
		{
			$this->token = $token;
			return $token;
		}
	}
	
	public function getToken()
	{
		$HttpSocket = new HttpSocket(array('ssl_verify_host'=>false));
		$data = array(
			'code'=>$this->authCode,
			'client_id'=>$this->id,
			'client_secret'=>$this->secret,
			'redirect_uri'=>FULL_BASE_URL.'/google/auth/',
			'grant_type'=>'authorization_code'
		);
		$result = $HttpSocket->post($this->tokenUri,$data);
		if(substr($result->code,0,1) == 2)
		{
			$this->token = json_decode($result,$assoc=true);
			$this->token['expires'] = date('Y-m-d H:i:s',strtotime("now")+$this->token['expires_in']);
			$this->Session->write('GoogleCalendar.token',$this->token);
			return $this->token;
		}
	}
	
	public function refreshToken()
	{
		$data = array(
			'client_id'=>$this->id,
			'client_secret'=>$this->secret,
			'refresh_token'=>$this->token['refresh_token'],
			'grant_type'=>'refresh_token'
		);
		$HttpSocket = new HttpSocket(array('ssl_verify_host'=>false));
		$result = $HttpSocket->post($this->tokenUri,$data);
		$this->token = json_decode($result,$assoc=true);
		if(substr($result->code,0,1) == 2)
		{
			$this->token = json_decode($result,$assoc=true);
			$this->token['expires'] = date('Y-m-d H:i:s',strtotime("now")+$this->token['expires_in']);
			$this->Session->write('GoogleCalendar.token',$this->token);
			return $this->token;
		}		
	}


	public function insertEvent($googleAccountID=null, $event=null)
	{
		$this->returning = null;
		$this->returnData = null;
		
		if(isset($googleAccountID))
			$this->Session->write('GoogleCalendar.googleAccountID',$googleAccountID);
		else
		{
			$this->googleAccountID = $this->Session->read('GoogleCalendar.googleAccountID');
			$googleAccountID = $this->googleAccountID;
		}
		
		if(isset($event))
		{
			$event = $this->eventMapping($event);
			$this->Session->write('GoogleCalendar.event',$event);
		}
		else
		{
			$event = $this->Session->read('GoogleCalendar.event');
		}
		
		$this->readToken();
		
		if(!isset($this->token))
		{
			if(!isset($this->authCode))
				return $this->authorize('insertEvent');
			$this->getToken();
		}

		if(isset($this->token))
		{
			if(CakeTime::isPast($this->token['expires']))
				$this->refreshToken();
			$data = json_encode($event);
			
			$request = array(
				'method'=>'POST',
				'body'=>$data,
				'header'=>array(
					'Authorization'=>$this->token['token_type'].' '.$this->token['access_token'],
					'Content-Type'=>'application/json'
				),
				'uri'=>array(
					'host'=>'www.googleapis.com',
					'path'=>sprintf('/calendar/v3/calendars/%s/events',$googleAccountID),
					'port'=>443
				)
			);
			
			$HttpSocket = new HttpSocket(
				array(
					'ssl_verify_host'=>false
				)
			);
			$result = $HttpSocket->request($request);
			
			if(substr($result->code,0,1)==2)
			{
				$this->Session->delete('GoogleCalendar');
				$event = json_decode($result->body,$assoc=true);
				$this->returnData = $event;
				$this->returning = true;
			}
			else
			{
				debug($request);
				debug($result);
				die;
			}
		}
	}
	
	public function getCalendarList($googleAccountID = null)
	{
		$this->returning = null;
		$this->returnData = null;
		
		if(isset($googleAccountID))
			$this->Session->write('GoogleCalendar.googleAccountID',$googleAccountID);
		else
		{
			$googleAccountID = $this->Session->read('GoogleCalendar.googleAccountID');
			$this->googleAccountID = $googleAccountID;
		}
			
		$this->readToken();
		
		if(!isset($this->token))
		{
			if(!isset($this->authCode))
				$this->authorize('getCalendarList');
			$this->getToken();
		}
		
		if(isset($this->token))
		{
			if(CakeTime::isPast($this->token['expires']))
				$this->refreshToken();
		
			$request = array(
				'method'=>'GET',
				'header'=>array(
					'Authorization'=>$this->token['token_type'].' '.$this->token['access_token']
				),
				'uri'=>array(
					'host'=>'www.googleapis.com',
					'path'=>'/calendar/v3/users/me/calendarList/'.$googleAccountID,
					'port'=>443
				)
			);
			
			$HttpSocket = new HttpSocket(array('ssl_verify_host'=>false));
			$response = $HttpSocket->request($request);
			
			if(substr($response->code,0,1)!=2)
			{
				debug($response);
				debug($response->code);
				die;
			}
			else
			{
				$this->returnData = json_decode($response,$assoc=true);
				return $this->returnData;
			}
		}
	}
}
?>
