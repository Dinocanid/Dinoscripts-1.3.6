<?php

namespace Controller\Main;
use Resource\Core\AppController;
use Resource\Core\Registry;

class BankController extends AppController{

    public function __construct(){
        parent::__construct("member");	
    }
	
	public function index(){
        $mysidia = Registry::get("mysidia");
        
        if($mysidia->input->post("deposit")){
            $amount = (int)$mysidia->input->post("amount");
	        $mysidia->user->bankDeposit($amount);
			return;
		}
		if($mysidia->input->post("withdraw")){
            $amount = (int)$mysidia->input->post("amount");
	        $mysidia->user->bankWithdraw($amount);
			return;
		}
	}
}