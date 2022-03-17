<?php

namespace View\Main;
use Model\DomainModel\Member;
use Model\DomainModel\MemberNotfoundException;
use Resource\Core\Registry;
use Resource\Core\View;
use Resource\GUI\Document\Comment;
use Resource\GUI\Document\Paragraph;
use Service\Builder\FormBuilder;

class BankView extends View{
	
	public function index(){
	    $mysidia = Registry::get("mysidia");
		$document = $this->document;
		$document->setTitle("The Bank");
		$document->add(new Comment("<hr>"));
		$bank = $mysidia->user->getBank();
		$cash = $mysidia->user->getMoney();
		
		if($mysidia->input->post("deposit")){
			$document->setTitle("Transaction Successful!");			
            $document->add(new Comment("You've just deposited {$mysidia->input->post("amount")} {$mysidia->settings->cost} to your bank account. <br/><br/>"));
			$document->add(new Comment("You'll be redirected back to the bank page within 3 seconds. Click <a href='../bank'>here</a> if your browser does not automatically redirect you."));
            $this->refresh(3);
			return;
		}
		
		if($mysidia->input->post("withdraw")){
			$document->setTitle("Transaction Successful!");
            $document->add(new Comment("You've just withdrawn {$mysidia->input->post("amount")} {$mysidia->settings->cost} from your bank account. <br/><br/>"));
            $document->add(new Comment("You'll be redirected back to the bank page within 3 seconds. Click <a href='../bank'>here</a> if your browser does not automatically redirect you."));
            $this->refresh(3);
			return;
		}
		
		if ($bank == 0){
			$document->add(new Comment("<h2>Current Balance: 0 {$mysidia->settings->cost}</h2>"));
		}
		else{
			$document->add(new Comment("<h2>Current Balance: {$bank} {$mysidia->settings->cost}</h2>", FALSE));
		}
		$document->add(new Paragraph);
		
		$bankForm = new FormBuilder("bankForm", "", "post");
		$bankForm->buildComment("Amount: ", FALSE)
			->buildTextField("amount", FALSE)
			->buildButton("Deposit", "deposit", "submit")
			->buildButton("Withdraw", "withdraw", "submit");
		$document->add($bankForm);
	}
}