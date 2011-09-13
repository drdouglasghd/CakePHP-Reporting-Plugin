<?php  
App::Import('Core', array('Router','Controller')); 
App::Import('Component', 'Email');

class EmailTask extends Shell { 
/** 
* Controller class 
* 
* @var Controller 
*/ 
    var $Controller; 

/** 
* EmailComponent 
* 
* @var EmailComponent 
*/ 
    var $Email; 

/** 
* List of default variables for EmailComponent 
* 
* @var array 
*/ 
    var $defaults = array( 
        'to'        => null, 
        'subject'   => null, 
        'charset'   => 'UTF-8', 
        'from'      => null, 
        'sendAs'    => 'html', 
        'template'  => null, 
        'debug'     => false, 
        'additionalParams'    => '', 
        'layout'    => 'default'
    ); 
	var $to = null;
	var $subject = null;
	var $cc = array();
	var $bcc = array();
	var $template = 'phantom';
	var $sent = null;
	var $smtpError = null;
	var $delivery = 'smtp';
/** 
* Startup for the EmailTask 
* 
*/ 
	function initialize() { 
		$this->Controller =& new Controller(); 
		$this->Email =& new EmailComponent(); 
		$this->Email->initialize($this->Controller);
		include CONFIGS . 'routes.php';
		$pluginPath = App::pluginPath('reporting');
		App::build(array( 'views' => array($pluginPath.'/views/')));
		$this->config = Configure::read('Phantom.email_options');
	} 

	function reset(){
		$this->Email->reset();
	}

/** 
* Send an email useing the EmailComponent 
* 
* @param array $settings 
* @return boolean 
*/ 
    function send($settings = array()) {
		$this->Email->from    = $this->config['from'];
		$this->Email->to      = $this->to;
		$this->Email->cc	  = $this->cc;
		$this->Email->bcc	  = $this->bcc;
		$this->Email->subject = $this->subject;
		$this->Email->smtpOptions = $this->config['smtpOptions'];
		$this->Email->delivery = $this->delivery;
		$this->Email->template = $this->template;
		$this->Email->sendAs = 'both';
		$this->sent = $this->Email->send();
		$this->htmlMessage = $this->Email->htmlMessage;
		$this->smtpError = $this->Email->smtpError;
		return $this->sent;
    } 

/** 
* Used to set view vars to the Controller so 
* that they will be available when the view render 
* the template 
* 
* @param string $name 
* @param mixed $data 
*/ 
    function set($name, $data) { 
        $this->Controller->set($name, $data); 
    } 

} 
?> 