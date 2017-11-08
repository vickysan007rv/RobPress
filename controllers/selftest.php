<?php

/** This class provides the Self Testing functionality
 * YOU MUST NOT EDIT THIS CLASS
 */
class Selftest extends Controller {

	//Configuration
	public $testUsername = 'Testing';
	public $testUsername2 = 'Testing2';
	public $testPassword = 'm5Ra-3Shi';

	public $mock;
	public $comments = array();

	/** WARNING: DO NOT EDIT THIS FILE **/
	public $tests = array(
		array(
			'name' => 'Debug mode',
			'description' => '',
		),	
		array(
			'name' => 'Creating a new account',
			'description' => '',
		),
		array(
			'name' => 'Logging in as an account',
			'description' => '',
		),
		array(
			'name' => 'Logging out as an account',
			'description' => '',
		),	
		array(
			'name' => 'Creating a new post',
			'description' => '',
		),	
		array(
			'name' => 'Creating a new comment',
			'description' => '',
		),	
		array(
			'name' => 'Performing a search',
			'description' => '',
		),	
		array(
			'name' => 'Creating a page',
			'description' => '',
		),	
		array(
			'name' => 'Uploading a file',
			'description' => '',
		),	
		array(
			'name' => 'Core infrastructure',
			'description' => '',
		),
		array(
			'name' => 'Common mistakes',
			'description' => '',
		),
	);

	public function index($f3) {
		$this->f3 = $f3;
		$this->mock = new Mock($f3);

		//Self test is only enabled in debug mode
		$settings = $this->Model->Settings;
		$debug = $settings->getSetting('debug');
		if(!$debug) {
			$f3->error('403');
			return false;
		}	

		//No user or not an admin
		$user_id = $f3->get('SESSION.user.id');
		$user_level = $f3->get('SESSION.user.level');

		if(empty($user_id) || $user_level < 2) {
			return $f3->reroute('/user/login');
		}

		//Start testing
		$this->mock->start();
		$this->before_tests();
		foreach($this->tests as $test_number=>$test) {
			$this->errors[$test_number] = '';

			//Run test
			$this->before_test($test_number);
			$result = call_user_func(array($this,"test_$test_number"));
			$this->after_test($test_number);

			$this->tests[$test_number]['result'] = $result;
			$this->tests[$test_number]['errors'] = $this->errors[$test_number];
		}
		$this->after_tests();
		$f3->set('tests',$this->tests);

		//End testing
		$this->mock->done();
		$this->f3->set('SESSION',$this->mock->session);
		$f3->set('session',$this->f3->get('SESSION'));
	}
	
	/** WARNING: DO NOT EDIT THIS FILE **/

	private function before_tests() {
		//Delete previous testing users
		$check = $this->Model->Users->fetch(array('username' => $this->testUsername));
		$check2 = $this->Model->Users->fetch(array('username' => $this->testUsername2));
		if(!empty($check)) { 
			$check->erase();
		} 
		if(!empty($check2)) {
			$check2->erase();
		}
		
		//Set up test user if doesnt exist
		$data = ['username' => $this->testUsername, 'displayname' => $this->testUsername, 'password' => $this->testPassword, 'password2' => $this->testPassword, 'email' => $this->testUsername . '@example.org'];
		$output = $this->mock->run('User/add',$data);
		if(!preg_match("!Registration complete!",$output)) {
			die('Unable to create testing user. Please ensure your registration process is working correctly in debug mode.');
		}

		//Promote test user to admin
		$check = $this->Model->Users->fetch(array('username' => $this->testUsername));
		if(!empty($check)) {
			//Ensure the testing user is an admin
			$check->level = 2;
			$check->save();
		} else {
			die('Unable to create testing user. Please ensure your registration process is working correctly in debug mode.');
		}
	}

	private function before_test($number) {
		//Log in as test user
		$data = ['username' => $this->testUsername, 'password' => $this->testPassword]; 
		$output = $this->mock->run('User/login',$data);
		if(!preg_match("!Logged in succesfully!",$output)) {
			die('Unable to log in as testing user. Please ensure your login process is working correctly in debug mode.');
		}
	}

	private function after_test($number) {
		
	}

	private function after_tests() {
		//Delete previous testing users
		$check = $this->Model->Users->fetch(array('username' => $this->testUsername));
		$check2 = $this->Model->Users->fetch(array('username' => $this->testUsername2));
		if(!empty($check)) { 
			$check->erase();
		} 
		if(!empty($check2)) {
			$check2->erase();
		}
	}

	//Enabling debug mode
	private function test_0() {
		$settings = $this->Model->Settings;
		$debug = $settings->setSetting('debug',0);
		$disabled = $settings->getSetting('debug');
		$debug = $settings->setSetting('debug',1);
		$enabled = $settings->getSetting('debug');

		$fail = 0;
	  if ($disabled != 0) { $this->errors[0] .= 'Turning off debug mode did not disable debug mode correctly. '; $fail = 1; }
		if ($enabled != 1) { $this->errors[0] .= 'Turning on debug mode did not enable debug mode correctly. '; $fail = 1; }
		
		$output = $this->mock->run('Blog/index');
		if (!preg_match('!Debug mode is enabled!',$output)) { 
			$this->errors[0] .= 'The debug mode header is not present in debug mode. '; 
			$fail = 1; 
		} 

		return !$fail;
	}

	//Create account
	private function test_1() {
		$data = ['username' => $this->testUsername2, 'displayname' => $this->testUsername2, 'password' => $this->testPassword, 'password2' => $this->testPassword, 'email' => $this->testUsername2 . '@example.org'];
		$output = $this->mock->run('User/add',$data);

		$fail = 0;
		if(!preg_match("!Registration complete!",$output)) {
			$this->errors[1] .= 'Registration form could not be submitted succesfully. ';
			$fail = 1;
		}

		$check = $this->Model->Users->fetch(array('username' => $this->testUsername2));
		if(empty($check)) {
			$this->errors[1] .= 'Registration did not create new user. ';
			$fail = 1;
		} else {
			$check->erase();
		}

		return !$fail;
	}
	
	//Log in
	public function test_2() {
		$data = ['username' => 'admin', 'password' => 'admin']; 
		$output = $this->mock->run('User/login',$data);

		//Check admin login form
		$fail = 0;
		if(!preg_match("!Logged in succesfully!",$output)) {
			$this->errors[2] .= 'It was not possible to log in using admin:admin in debug mode. ';
			$fail = 1;	
		}

		//Create test user 
		$data = ['username' => $this->testUsername2, 'displayname' => $this->testUsername2, 'password' => $this->testPassword, 'password2' => $this->testPassword , 'email' => $this->testUsername2 . '@example.org'];
		$output = $this->mock->run('User/add',$data);
		$check = $this->Model->Users->fetch(array('username' => $this->testUsername2));
		if(empty($check)) {
			$this->errors[2] .= 'Registration did not create new user. ';
			$fail = 1;
		} 

		$data = ['username' => $this->testUsername2, 'password' => $this->testPassword];
		$output = $this->mock->run('User/login',$data);

		//Check normal login form
		$fail = 0;
		if(!preg_match("!Logged in succesfully!",$output)) {
			$this->errors[2] .= 'It was not possible to log in using the log in form.';
			$fail = 1;	
		}

		//Check login function
		$result = $this->Auth->login($this->testUsername2,$this->testPassword);
		if(!$result) {
			$this->errors[2] .= 'The login function in the AuthHelper did not log in a valid user. ';
			$fail = 1;	
		}

		//Check debug login function
		$result = $this->Auth->debugLogin($this->testUsername2,$this->testPassword);
		if(!$result) {
			$this->errors[2] .= 'The debugLogin function in the AuthHelper did not log in a valid user. ';
			$fail = 1;	
		}

		//Check debug login function
		$result = $this->Auth->specialLogin($this->testUsername2);
		if(!$result) {
			$this->errors[2] .= 'The specialLogin function in the AuthHelper did not log in a valid user. ';
			$fail = 1;	
		}

		//Remove dummy user
		if(!empty($check)) {
			$check->erase();
		}

		return !$fail;
	}
	
	//Log out
	public function test_3() {
		$output = $this->mock->run('User/logout');
		$fail = 0;
		if(!preg_match("!Logged out succesfully!",$output)) {
			$this->errors[3] .= 'The logout function did not work correctly. ';
			$fail = 1;
		}

		return !$fail;
	}
	
	//Create post
	public function test_4() {
		$data = array('title' => 'A test blog post', 'summary' => 'Hello Test World!', 'content' => 'Hello Test World!', 'published' => 0, 'Publish' => 'Publish');
		$output = $this->mock->run('Admin/Blog/add',$data);

		$fail = 0;
		if(!preg_match("!Post added succesfully!",$output)) {
			$this->errors[4] .= 'Unable to add a new post through the admin form. ';
			$fail = 1;
		}

		$check = $this->Model->Posts->fetch(array('title' => 'A test blog post'));
		if(empty($check)) {
			$this->errors[4] .= 'Unable to find the newly created post in the database. ';
			$fail = 1;
		}

		$output = $this->mock->run('Blog/index',$data);
		if(!preg_match("!Hello Test World!",$output)) {
			$this->errors[4] .= 'Unable to find the newly created post on the index. ';
			$fail = 1;
		}

		$allTests = $this->Model->Posts->fetchAll(array('title' => 'A test blog post'));
		foreach($allTests as $testPost) {
			$testPost->erase();
		}

		return !$fail;
	}
	
	//Create comment
	public function test_5() {

		$data = array('title' => 'A test blog post', 'summary' => 'Hello Test World!', 'content' => 'Hello Test World!', 'published' => 0, 'Publish' => 'Publish');
		$output = $this->mock->run('Admin/Blog/add',$data);

		$fail = 0;
		if(!preg_match("!Post added succesfully!",$output)) {
			$this->errors[5] .= 'Unable to add a new post through the admin form. ';
			$fail = 1;
		}

		$post = $this->Model->Posts->fetch(array('title' => 'A test blog post'));
		if(empty($post)) {
			$this->errors[5] .= 'Failed to create a new post and find it in the database. ';
			$fail = 1;
		}

		$me = $this->Model->Users->fetch(array('username' => $this->testUsername));
		$data = array('subject' => 'This is my comment', 'message' => 'This is my message', 'user_id' => $me->id);
		$output = $this->mock->run('Blog/comment/' . $post->id,$data);

		if(!preg_match("!Your comment has been posted!",$output)) {
			$this->errors[5] .= 'Unable to add a new comment through the new comment form. ';
			$fail = 1;		
		}

		$comment = $this->Model->Comments->fetch(array('subject' => 'This is my comment'));
		if(empty($comment)) {
			$this->errors[5] .= 'Unable to find new comment in the database. ';
			$fail = 1;		
		}

		if(!empty($comment)) {
			$post->erase();
			$comment->erase();
		}

		return !$fail;
	}
	
	//Perform search
	public function test_6() {
		$data = array('title' => 'Come and find me', 'summary' => 'Come and find me', 'content' => 'Come and find me', 'published' => 0, 'Publish' => 'Publish');
		$output = $this->mock->run('Admin/Blog/add',$data);

		$fail = 0;

		$data = array('search' => 'Come and find');
		$output = $this->mock->run('Blog/search',$data);
		if(!preg_match("!Come and find me!",$output)) {
			$this->errors[6] .= 'Unable to find a valid post using the search form. ';
			$fail = 1;
		}

		$allTests = $this->Model->Posts->fetchAll(array('title' => 'Come and find me'));
		foreach($allTests as $testPost) {
			$testPost->erase();
		}

		return !$fail;
	}

	//Creating a page
	public function test_7() {
		$fail = 0;

		$data = array('title' => 'Testing');
		$output = $this->mock->run('Admin/page/add',$data);
		if(!preg_match("!Page created succesfully!",$output)) {
			$this->errors[7] .= 'Unable to add a page through the admin interface. ';
			$fail = 1;			
		}

		$data = array('content' => 'This is a test');
		$output = $this->mock->run('Admin/page/edit/testing',$data);
		if(!preg_match("!Page updated succesfully!",$output)) {
			$this->errors[7] .= 'Unable to edit a page through the admin interface. ';
			$fail = 1;			
		}

		$output = $this->mock->run('Page/display/testing');
		if(!preg_match("!This is a test!",$output)) {
			$this->errors[7] .= 'Page was not succesfully created with given name and content. ';
			$fail = 1;			
		}

		if(file_exists(getcwd() . '/pages/testing.html')) {
			unlink(getcwd() . '/pages/testing.html');
		}

		return !$fail;
	}

	//Uploading a file
	public function test_8() {
		$fail = 0;
		$files = array('tmp_name' => getcwd () . '/uploads/rob1.jpg', 'name' => 'test.jpg', 'size' => filesize(getcwd() . '/uploads/rob1.jpg'), 'error' => 0, 'type' => 'image/jpeg');

		if(!file_exists(getcwd() . '/uploads/rob1.jpg')) {
			$fail = 1;
			$this->errors[8] .= 'Essential rob imagery is missing (uploads/rob.jpg). ';
		}
		
		$result = File::upload($files,true);

		if ($result === false || !preg_match("!uploads/!",$result) || !file_exists(getcwd() . $result)) {
			$fail = 1;			
			$this->errors[8] .= 'Unable to upload file using the upload function. ';
		}

		if(!$fail && filesize(getcwd() . $result) < 1) {
			$fail = 1;
			$this->errors[8] .= 'Uploaded file was empty. ';
		}

		if(file_exists(getcwd() . $result) && !empty($result)) {
			unlink(getcwd() . $result);
		}

		return !$fail;
	}

	//Core infrastructure
	public function test_9() {
		$fail = 0;
		$output = $this->mock->make_request('initialise.php');
		if($output === false) {
			$fail = 1;
			$this->errors[9] .= 'It was not possible to run the required initialise.php file';
		}
		return !$fail;
	}

	//Common mistakes
	public function test_10() {
		$fail = 0;
		$dir = getcwd();
		$referer = `grep --include=*.php --exclude selftest.php --exclude-dir lib -R 'REFERER' $dir`;
		if(!empty($referer)) {
			$fail = 1;
			$this->errors[10] .= 'Do not check or use the referer, it causes more problems than it solves! ';
		}

		$root = `grep -R --include=*.php --exclude selftest.php --exclude-dir lib 'DOCUMENT_ROOT' $dir`;
		if(!empty($root)) {
			$fail = 1;
			$this->errors[10] .= 'You have made use of DOCUMENT_ROOT - do not rely on this, as your application may not always be in the root! Don\'t use it';
		}

		$sleep = `grep -R --include=*.php --exclude selftest.php --exclude-dir lib 'sleep(' $dir`;
		if(!empty($sleep)) {
			$fail = 1;
			$this->errors[10] .= 'Do not make use of the sleep functions, these just lead to denial of service potential on the server.';
		}
			
		return !$fail;
	}

}


?>

