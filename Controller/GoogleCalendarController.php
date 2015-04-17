<?php
class GoogleCalendarController extends GoogleCalendarAppController
{
	public $components = array('Session');
	
	public function beforeFilter()
	{
		$this->autoRender = false;
	}
	
	public function auth()
	{
		if(isset($this->request->query['code']))
		{
			$this->GoogleCalendar->authCode = $this->request->query['code'];
			$this->GoogleCalendar->state = $this->request->query['state'];
		}
		elseif(isset($this->request->query['error']))
			return $this->redirect($this->referer());
		call_user_func(array($this->GoogleCalendar,$this->GoogleCalendar->state));
		if(isset($this->GoogleCalendar->returning))
			return $this->GoogleCalendar->returnData;
	}
}
?>
