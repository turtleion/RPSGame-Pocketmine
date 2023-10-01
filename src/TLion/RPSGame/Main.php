<?php
namespace TLion\RPSGame;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;
use jojoe77777\FormAPI\SimpleForm;

class Main extends PluginBase implements Listener {
	private array $rps_queue = [];
	private array $playerList = [];
	private array $busy = [];
	public function onEnable():
	void {
		// @mkdir($this->getDefaultFolder());
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		//$this->saveResource();
	}

	private function inform(string $info, string $text): string {
		return "[ " . C::GREEN . $info . C::WHITE . " ] " . $text;
	}
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args):
	bool {
		switch ($cmd->getName()) {
			case "rps":
//				$sender->sendMessage(implode(", ", $args));
//				$sender->sendMessage(implode(", ", $this->getServer()->getOnlinePlayers()));
				if (!$sender instanceof Player) {
					$this->getLogger()->info("You cannot run this command on console.");
					return false;
				}
				if (isset($this->rps_queue[$sender->getName() ])) {
					unset($this->rps_queue[$sender->getName() ]);
				}
				if (!isset($args[0])) {
					$sender->sendMessage($this->inform("ERROR", "Please provide the target player."));
					return false;
				}
				$player1 = $sender;
				$pl_fnd = false;
				foreach ($this->getServer()->getOnlinePlayers() as $player) {
					if ($player->getName() == $args[0]) {
						$pl_fnd = true;
					}
				}
				if (!$pl_fnd) {
					$sender->sendMessage($this->inform("INFO", "Player doesn't exist"));
					return false;
				}
				//if (!in_array($args[0], $this->getServer()->getOnlinePlayers())){
				//	$sender->sendMessage("Player doesn't exist");
				//        return false;
				//}


				if ($args[0] == $sender->getName()){
					$sender->sendMessage($this->inform("WARN", "You can't make a request with yourself."));
					return false;
				}
				$player2 = $this->getServer()->getPlayerExact($args[0]);
				if (!$player2) {
					$sender->sendMessage($this->inform("ERROR", "Failed to connect to the player."));
					return false;
				}

				if (in_array($sender->getName(), $this->busy)){
					$sender->sendMessage($this->inform("ERROR", "You couldn't make a new request while you have made an unconfirmed request."));
					return false;
				}


				$_n = null;
				// 0-5 > Rock
				// 6-10 > Paper
				// 11-20 > Scissor
				if (!isset($args[1])) {
					$_n = rand(0, 20);
				} else {
					$_n = $args[0];
				}
				if ($_n > - 1 && $_n < 6) {
					$_n = "rock";
				} elseif ($_n > 5 && $_n < 11) {
					$_n = "paper";
				} elseif ($_n > 11 && $_n < 21) {
					$_n = "scissor";
				} else {
					$_n = "scissor";
				}
				// [Papa] = [Hexa = rock]
				$this->rps_queue[$player2->getName() ] = $player1->getName() . "-:SEPARATOR:-" . $_n;
				$this->busy[] = $player2->getName();
				$sender->sendMessage($this->inform("INFO", "Request sent."));

				$player2->sendMessage(C::GREEN . "---------" . C::WHITE . "\nYou have a RPSGame request from: " . C::GREEN . $sender->getName() . C::WHITE . "\nYou can accept it by typing, " . C::YELLOW . "/" . C::WHITE . "rpsaccept");
				// Set timer
				$player2->sendMessage("You have 10" . C::GREEN . "s" . C::WHITE . " to answer the request");
				$t1 = $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use (&$t1, $player2, $sender) {
					if (isset($this->rps_queue[$player2->getName() ])) {
						unset($this->rps_queue[$player2->getName() ]);
						unset($this->busy[array_search($player2->getName(), $this->busy)]);
						$sender->sendMessage("[ Request" . C::YELLOW . "/" . C::WHITE . $player2->getName() . C::YELLOW . " " . C::WHITE . $sender->getName() . " ] Player " . $player2->getName() . " has rejected the request.");
						$player2->sendMessage("[ Request" . C::YELLOW . "/" . C::WHITE . $player2->getName() . C::YELLOW . " " . C::WHITE . $sender->getName() . " ] Request Rejected");

					}
				}), 400);
				return true;
			// Opponent

			case "rpsaccept":
				if (isset($this->rps_queue[$sender->getName()])) {
					// [ P1 = .. , P2 = .. ]
					$_p = null;
					// 0-5 > Rock
					// 6-10 > Paper
					// 11-20 > Scissor
					$_p = rand(0, 20);

					if ($_p > - 1 && $_p < 6) {
						$_p = "rock";
					} elseif ($_p > 5 && $_p < 11) {
						$_p = "paper";
					} elseif ($_p > 11 && $_p < 21) {
						$_p = "scissor";
					} else {
						$_p = "scissor";
					}

					if (!isset($args[0])) {
						$sender->sendMessage("[ " . C::YELLOW . "INFO" . C::WHITE . " ]" . " You have not choose an answer, the system randomly choosen your answer: " . C::GREEN . $_p . C::WHITE);
					} else {
						$_p = $args[0];
					}
					//$this->rps_queue[$sender->getName()][
					//    $sender->getName()
					//] = $_p;
					$p1_c = $_p;
					$p2_c = explode("-:SEPARATOR:-", $this->rps_queue[$sender->getName() ]) [1];
					$winner = "";
					//$p1_c = "paper";
					// ROCK AND PAPER
					$p1 = $sender;
					$p2 = $this->getServer()->getPlayerExact(explode("-:SEPARATOR:-", $this->rps_queue[$sender->getName() ]) [0]);
					if ($p1_c == "rock" && $p2_c == "paper") {
						$winner = $p2->getName();
					} elseif ($p1_c == "paper" && $p2_c == "rock") {
						$winner = $p1->getName();
					}
					// SCISSOR AND PAPER
					if ($p1_c == "scissor" && $p2_c == "paper") {
						$winner = $p1->getName();
					} elseif ($p1_c == "paper" && $p2_c == "scissor") {
						$winner = $p2->getName();
					}
					// ROCK AND SCISSOR
					if ($p1_c == "rock" && $p2_c == "scissor") {
						$winner = $p1->getName();
					} elseif ($p1_c == "scissor" && $p2_c == "rock") {
						$winner = $p2->getName();
					}

					if ($p1_c == "rock" && $p2_c == "rock"){
						$winner = " DRAW ";
					} elseif ($p1_c == "scissor" && $p2_c == "scissor"){
						$winner = " DRAW ";
					} elseif ($p1_c == "paper" && $p2_c == "paper"){
						$winner = " DRAW ";
					}
					$p1->sendMessage("--------");
					$p2->sendMessage("--------");
					$p1->sendMessage("Your Choice: " . $p1_c);
					$p2->sendMessage("Your Choice: " . $p2_c);
					$p1->sendMessage("Opponent Choice: " . $p2_c);
					$p2->sendMessage("Opponent Choice: " . $p1_c);
					$p1->sendMessage("Winner: " . $winner);
					$p2->sendMessage("Winner: " . $winner);
					unset($this->rps_queue[$sender->getName() ]);
					return true;
				} else {
					$sender->sendMessage("Couldn't found a request within your username.");
					return false;
				}
			case "rpsinfo":
				$sender->sendMessage("[ " . C::GREEN . "INFO" . C::WHITE . " ] RPSGame v1.1.0.2");
				break;

			case "rpsg":
				if ($sender instanceof Player){
					$this->MainForm($sender);
				}
		}
		return true;
	}

	public function PlayGameGUI (Player $pl): SimpleForm {
		$form = new 
	}

	public function MainForm(Player $player): SimpleForm
	{
		$form = new SimpleForm(function (Player $player, int $data = null) {
			if ($data === null){
				return true;
			}

			switch ($data){
				case 0:
					$this->PlayGameGUI($player);
				case 1:
					break;
			}
		});
		$form->setTitle("RPS GUI v1");
		$form->setContent("Select player to play with.");

		$form->addButton("Play Game");
		$form->addButton("Exit");
		$form->sendToPlayer($player);
		return $form;
	}
}
