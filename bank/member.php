<?php

namespace Model\DomainModel;
use ArrayObject;
use Resource\Core\Model;
use Resource\Core\Registry;
use Resource\Exception\InvalidActionException;
use Resource\Native\MysString;
use Resource\Utility\Date;

class Member extends User{ 
    
    protected $salt;
    protected $password;
    protected $session;
    protected $email;
    protected $birthday;
    protected $membersince;
    protected $money; 
    protected $bank;
    protected $friends; 
    
    protected $profile;
    protected $contact;
    protected $option;
    protected $permission;
    
    public function __construct($userinfo, $dto = NULL, $loadProfile = FALSE){
	    $mysidia = Registry::get("mysidia");
	    if($userinfo instanceof MysString) $userinfo = $userinfo->getValue();
        if(!$dto){
            $prefix = constant("PREFIX");
	        $userinfo = ($userinfo == "SYSTEM") ? $mysidia->settings->systemuser : $userinfo;
	        $whereclause = is_numeric($userinfo) ? "{$prefix}users.uid = :userinfo" : "username = :userinfo";
            $values = ["userinfo" => $userinfo];
            $stmt = $loadProfile ? $mysidia->db->join("users_profile", "users_profile.uid = users.uid")->select("users", [], $whereclause, $values) 
                                 : $mysidia->db->select("users", [], $whereclause, $values);
	        $dto = $stmt->fetchObject();
            if(!is_object($dto)) throw new MemberNotfoundException("The specified user {$userinfo} does not exist...");
        }
        parent::__construct($dto);
        if($loadProfile) $this->createProfileFromDTO($dto);
    }
    
    protected function createFromDTO($dto){
        parent::createFromDTO($dto);
        $this->birthday = new Date($dto->birthday);
        $this->membersince = new Date($dto->membersince);
    }
    
    protected function createProfileFromDTO($dto){
        $this->profile = new UserProfile($this->uid, $dto, $this);
    }
    
    public function getSalt(){
        return $this->salt;
    }
    
    public function getPassword(){
        return $this->password;
    }
    
    public function setPassword($password, $assignMode = ""){
        if($assignMode == Model::UPDATE) $this->save("password", $password);
        $this->password = $password;
    }
    
    public function getSession(){
        return $this->session;
    }
    
    public function getEmail(){
        return $this->email;
    }
    
    public function setEmail($email, $assignMode = ""){
        if($assignMode == Model::UPDATE) $this->save("email", $email);
        $this->email = $email;
    }
    
    public function getBirthday($format = NULL){
        return $format ? $this->birthday->format($format) : $this->birthday;
    }
    
    public function getMemberSince($format = NULL){
        return $format ? $this->membersince->format($format) : $this->membersince;
    }
    
    public function getMoney(){
        return $this->money;
    }
    
    public function getBank(){
        return $this->bank;
    }
    
    public function getFriends(){
        return $this->friends;
    }
    
    public function getProfile(){
        if(!$this->profile) $this->profile = new UserProfile($this->uid, NULL, $this);
        return $this->profile;
    }       
    
    public function getContact(){
        if(!$this->contact) $this->contact = new UserContact($this->uid, NULL, $this);
        return $this->contact;
    }
    
    public function getOption(){
        if(!$this->option) $this->option = new UserOption($this->uid, NULL, $this);
        return $this->option;
    }
    
    public function getPermission(){
        if(!$this->permission) $this->permission = new UserPermission($this->uid, NULL, $this);
        return $this->permission;
    }
    
    public function hasPermission($perms){
        return $this->getPermission()->hasPermission($perms);
    }
    
    public function isCurrentUser(){
      $mysidia = Registry::get("mysidia");
      return ($this->uid == $mysidia->user->getID() && $this->username == $mysidia->user->getUsername());        
    }

    public function isLoggedIn(){
        return TRUE;
    }
    
    public function isAdmin(){
        if($this->usergroup == 1 || $this->usergroup == 2) return TRUE;
        if($this->usergroup == 0 || $this->usergroup == 3 || $this->usergroup == 4 || $this->usergroup == 5) return FALSE;
        return ($this->getUsergroup(Model::MODEL)->getPermission("cancp") == "yes");
    }
    
    public function isBanned(){ 
        return ($this->usergroup == 5);
    }
    
    public function ban(){
        if($this->isAdmin()) return;
        $mysidia = Registry::get("mysidia");
        $this->usergroup = 5;
        $this->save("usergroup", $this->usergroup);
        $mysidia->db->update("users_permissions", ["canlevel" => 'no', "canvm" => 'no', "canfriend" => 'no', "cantrade" => 'no', "canbreed" => 'no', "canpound" => 'no', "canshop" => 'no'], "uid = '{$this->uid}'");    
    }
    
    public function unban(){ 
        $mysidia = Registry::get("mysidia");
        $this->usergroup = 3;
        $this->save("usergroup", $this->usergroup);
	    $mysidia->db->update("users_permissions", ["canlevel" => 'yes', "canvm" => 'yes', "canfriend" => 'yes', "cantrade" => 'yes', "canbreed" => 'yes', "canpound" => 'yes', "canshop" => 'yes'], "uid = '{$this->uid}'");         
    }
    
    public function delete(){
        $mysidia = Registry::get("mysidia");
        $mysidia->db->delete("users", "uid = '{$this->uid}'");
        $mysidia->db->delete("users_contacts", "uid = '{$this->uid}'");
	    $mysidia->db->delete("users_options", "uid = '{$this->uid}'");
        $mysidia->db->delete("users_permissions", "uid = '{$this->uid}'");
	    $mysidia->db->delete("users_profile", "uid = '{$this->uid}'");
        $mysidia->db->update("owned_adoptables", ["owner" => 0], "owner = '{$this->uid}'");
        $mysidia->db->update("pounds", ["currentowner" => 0], "currentowner = '{$this->uid}'");
        $mysidia->db->delete("inventory", "owner = '{$this->uid}'"); 
    }

    public function getTheme(){
         $mysidia = Registry::get("mysidia");
         $theme = $mysidia->db->select("users_options", ["theme"], "uid = '{$this->uid}'")->fetchColumn();
	     return $theme;
    }
    
    public function getVotes(Date $time = NULL) {
	    $mysidia = Registry::get("mysidia");
        if(!$time) $time = new Date;
        $numVotes = $mysidia->db->select("vote_voters", ["void"], "userid = '{$this->uid}' and date = '{$time->format('Y-m-d')}'")->rowCount();
        return $numVotes;              
    }
    
    public function changeMoney($amount){     
        if(!is_numeric($amount)) throw new InvalidActionException('Cannot change user money by a non-numeric value!');	  
	    $this->money += $amount;    
	    if($this->money >= 0){ 
            $this->save("money", $this->money);
		    return TRUE;		  	
	    }
	    else throw new InvalidActionException("It seems that you cannot afford this transaction.");
    }
    
    public function bankDeposit($amount){     
        if(!is_numeric($amount)) throw new InvalidActionException("Cannot change bank total by a non-numeric value! Click <a href='../bank'>here</a> to return to the bank.");
        
        if($amount <= 0) throw new InvalidActionException("Deposited amount must not be empty or a negative number! Click <a href='../bank'>here</a> to return to the bank.");
	    
	    if($this->money >= $amount){
	        $this->money -= $amount;
	        $this->bank += $amount;
            $this->save("bank", $this->bank);
            $this->save("money", $this->money);
	    }
        else{
            throw new InvalidActionException("It seems that you cannot afford this transaction. Click <a href='../bank'>here</a> to return to the bank.");
	        }
    }
    
    public function bankWithdraw($amount){     
        if(!is_numeric($amount)) throw new InvalidActionException("Cannot change bank total by a non-numeric value! Click <a href='../bank'>here</a> to return to the bank.");
        
        if($amount <= 0) throw new InvalidActionException("Deposited amount must not be empty or a negative number! Click <a href='../bank'>here</a> to return to the bank.");
        
	    if($this->bank >= $amount){
	        $this->bank -= $amount;
	        $this->money += $amount;
	        $this->save("money", $this->money);
            $this->save("bank", $this->bank);
	    }
        else{
            throw new InvalidActionException("Bank total cannot be negative! Click <a href='../bank'>here</a> to return to the bank.");
	        }
    }
    
    public function payMoney($amount){ 
        return $this->changeMoney(-$amount);
    }
    
    public function countOwnedAdopts(){
        $mysidia = Registry::get("mysidia");
        $stmt = $mysidia->db->select("owned_adoptables", ["aid"], "owner = '{$this->uid}'");
	    return $stmt->rowCount();
    }
    
    public function getOwnedAdopts(){
		$mysidia = Registry::get("mysidia");
        $stmt = $mysidia->db->join("adoptables", "adoptables.id = owned_adoptables.adopt")
                        ->select("owned_adoptables", [], "owner = '{$this->uid}'");
        $adopts = new ArrayObject;
        while($dto = $stmt->fetchObject()){
            $adopts[] = new OwnedAdoptable($dto->aid, $dto);
        }
        return $adopts;
    }
    
    public function countFriends(){
        if(!$this->friends) return 0;
        return count($this->getFriendsList());
    }
    
    public function hasFriends(){
        return ($this->countFriends() == 0);
    }
    
    public function isFriend(User $user = NULL){
        if(!$user || !$this->friends) return FALSE;
        $friends = explode(",", $this->friends);
        return in_array($user->getID(), $friends);
    }
    
    public function getFriendsList($fetchMode = ""){
        if(!$this->friends) return NULL;
        if($fetchMode == Model::MODEL){
            $mysidia = Registry::get("mysidia");
            $prefix = constant("PREFIX");
            $friendsList = new ArrayObject;
            $stmt = $mysidia->db->join("users_profile", "users_profile.uid = users.uid")
                            ->select("users", [], "{$prefix}users.uid IN ({$this->friends})");
            while($dto = $stmt->fetchObject()){
                $friendsList[] = new static($dto->uid, $dto, TRUE);
            }
            return $friendsList;
        }
        return explode(",", $this->friends);
    }
    
    public function addFriend($uid = NULL){
        if(!$uid || $uid == $this->uid) return;
        $friends = $this->getFriendsList();
        $friends[] = $uid;
        sort($friends);
        $this->friends = implode(",", $friends);
        $this->save("friends", $this->friends);
    }
    
    public function removeFriend($uid = NULL){ 
        if(!$uid || $uid == $this->uid) return;
        $friends = $this->getFriendsList();
        $index = array_search($uid, $friends);
        if($index === FALSE) return;
        unset($friends[$index]);
        $this->friends = implode(",", $friends);
        $this->save("friends", $this->friends);            
    }
    
    public function countMessages($folder = "inbox"){
        $mysidia = Registry::get("mysidia");
	    $table = ($folder == "inbox") ? "messages" : "folders_messages"; 
        $whereclause = ($folder == "inbox") ? "touser='{$this->uid}'" : "touser='{$this->uid}' AND folder='{$folder}' ORDER BY mid DESC";
	    $stmt = $mysidia->db->select($table, ["touser"], $whereclause);	
	    return $stmt->rowCount();        
    }
    
    public function getMessages($folder = "inbox"){
        $mysidia = Registry::get("mysidia");
	    $table = ($folder == "inbox") ? "messages" : "folders_messages"; 
        $whereclause = ($folder == "inbox") ? "touser='{$this->uid}'" : "touser='{$this->uid}' AND folder='{$folder}' ORDER BY mid DESC";
	    $stmt = $mysidia->db->select($table, [], $whereclause);	
        $messages = new ArrayObject;
        while($dto = $stmt->fetchObject()){
            $messages[] = new PrivateMessage($dto->mid, $folder, $dto);
        }
        return $messages;
    }
    
    public function donate(Member $recipient, $amount){
        $mysidia = Registry::get("mysidia");
        $this->changeMoney(-$amount);
        $recipient->changeMoney($amount);
        
        $donateMessage = new PrivateMessage;
        $donateMessage->setSender($this);
        $donateMessage->setRecipient($recipient);
        $donateMessage->setMessage("Donation from {$this->username}", 
                                   "{$this->username} has donated {$amount} {$mysidia->settings->cost} to you, you may use this money immediately for any purposes.");
        $donateMessage->post();
    }
    
    public function createPasswordReset(){
        $mysidia = Registry::get("mysidia");
        $resetCode = $this->generateCode();
        $today = new Date;
        $mysidia->db->insert("passwordresets", ["id" => NULL, "username" => $this->username, "email" => $this->email, "code" => $resetCode, "ip" => $_SERVER['REMOTE_ADDR'], "date" => $today->format('Y-m-d')]);
        return new PasswordReset(NULL, $this->username);
    }
    
    public function sendPasswordEmail($newPass){
        $mysidia = Registry::get("mysidia");
        $systememail = $mysidia->settings->systememail;
		$headers = "From: {$systememail}";
		$message = "Hello {$this->username};\n\nYour password at {$mysidia->settings->sitename} has been changed by the site admin. Your new account details are as follows:\n
			        Username: {$this->username}\n
                    Password: {$newPass}\n
				    You can log in to your account at: {$mysidia->path->getAbsolute()}login\n
				    Thank You. The {$mysidia->settings->sitename} team.";
					mail($this->email, "{$mysidia->settings->sitename} - Your password has been changed", $message, $headers);	        
    }
    
    protected function save($field, $value) {
		$mysidia = Registry::get("mysidia");
		$mysidia->db->update("users", [$field => $value], "uid='{$this->uid}'");        
    }
}