<?php  
Class Short {
	private $conn;
    public function __construct($conn) {
        $this->conn=$conn;
	}
	
	public function dest($id){
		$row = $this->conn->prepare("SELECT linkAddress FROM shorteners WHERE id = :id");
		$row->bindParam(":id", $id);
		$row->execute();
		return $row->fetchColumn();
	}
	public function getShortened($url) {
		$row = $this->conn->prepare("SELECT id FROM shorteners WHERE linkAddress = :url");
		$row->bindParam(":url", $url);
		$row->execute();
		if($row->rowCount()>0) {
			return $row->fetchColumn();
		}
		else {
			$count=1;
			while($count!=0) {
				$id = rand(1000,9999);
				$row = $this->conn->prepare("SELECT * FROM shorteners WHERE id = :id");
				$row->bindParam(":id", $id);
				$row->execute();
				$count=$row->rowCount();
			}
			$row=$this->conn->prepare("INSERT INTO shorteners (id, linkAddress) VALUES (:id, :url)");
			$row->bindParam(":id", $id);
			$row->bindParam(":url", $url);
			if($row->execute())
				return $id;
		}
	}
}
	
?>