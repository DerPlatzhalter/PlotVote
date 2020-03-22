<?php

namespace wortie\PlotVote;

use CrestStats\session\Session;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use MCATECH\SimpleCoins\SimpleCoins;
use wortie\PlotVote\PlotVote;
use MyPlot\Plot;

class PlotVoteDatabase {
    
    /** @var \SQLite3 */
    private $sqlite;
    
    private $plugin;
	
	private $sqlGetPlot;

    public function __construct(PlotVote $plugin) {
        $this->plugin = $plugin;
        $this->sqlite = new \SQLite3($plugin->getDataFolder() . "plotvotes.db");
        $this->initialize();
    }
    
    public function initialize() {
        $this->sqlite->exec("CREATE TABLE IF NOT EXISTS plots (
         username VARCHAR(16) PRIMARY KEY,
		 plot INT,
		 plotvotes INT
        )");
    }

    public function regPlotEnty($owner, Plot $plot, int $votes) { #Should fix Error when player has multiple plots
        $stmt = $this->sqlite->prepare("INSERT or IGNORE INTO plots (
          username,
          plot,
		  plotvotes
        )
        VALUES (
          :username,
          :plot,
		  0
        )");
        $stmt->bindValue(":username", $owner);
        $stmt->bindValue(":plot", $this->plugin->getPlotById($plot));
        $stmt->execute();
    }
	
	public function remPlotEnty(Player $player, Plot $plot, int $votes) {
		# TODO
    }
	
	# TODO add api for multiple plots, all must be saved by unique ID not name
	
	public function getPlotVotes($plot){
		$votes = $this->sqlite->querySingle("SELECT plotvotes FROM plots WHERE plot='$plot'");
        return $votes;
	}
	
	public static function verifyPlayerInDB(string $player): bool {
		$username = $this->sqlite->querySingle("SELECT username FROM plots WHERE username = '$player'");
		if ($username == null) {
			return 'false';
		} else {
			return 'true';
		}
	}
	
	
	public function setVotes($plot) {
		$votes = $this->getPlotVotes($plot);
		$result = $votes + 1;
		$stmt = $this->sqlite->prepare("UPDATE plots SET plotvotes='$result' WHERE plot='$plot'");
		$result = $stmt->execute();
		$result->finalize();
	}
	
	public function resetVotes($plot) {
		$result = 0;
		$stmt = $this->sqlite->prepare("UPDATE plots SET plotvotes='$result' WHERE plot='$plot'");
		$result = $stmt->execute();
		$result->finalize();
	}
    
    /**
     * @return string
     */
    public function getTop(): string {
        $result = $this->sqlite->query("SELECT * FROM (SELECT * FROM plots ORDER BY plotvotes DESC)orderedusers
        LIMIT 10");
        
        $message = "§7Top Plots: ";
        
        $end = false;
        for($i = 1; $i <= 10; $i++) {
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if(is_array($row) and !($end)) {
                $message .= "\n{$i}. {$row["username"]} §7with Votes§a {$row["plotvotes"]}§7";
            } else {
                $message .= "\n{$i}. §7Unknown";
                $end = true;
            }
        }
        
        $result->finalize();
        return $message;
    }
}