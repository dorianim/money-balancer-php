
<?php

class StorageHelper
{

    private $_dataDir;

    public function __construct($dataDir)
    {
        $this->_dataDir = $dataDir;
    }

    function loadUserBalance($balance, $thisUser)
    {
        $data = $this->_loadBalanceData($balance);

        $totalSum = 0;
        $thisUsersSum = 0;

        foreach ($data as $user => $purchases) {
            $userSum = 0;
            foreach ($purchases as $purchase) {
                $userSum += $purchase["amount"];
            }
            $totalSum += $userSum;
            if ($user == $thisUser) {
                $thisUsersSum = $userSum;
            }
        }

        $userCount = count(array_keys($data));
        $average = $totalSum / $userCount;

        return ($thisUsersSum - $average) * $userCount;
    }

    function addPurchase($balance, $thisUser, $name, $amount, $time)
    {
        if ($amount > 0) {
            $data = $this->_loadBalanceData($balance);
            if (!isset($data[$thisUser])) {
                $data[$thisUser] = array();
            }

            array_push($data[$thisUser], array(
                "name" => $name,
                "amount" => $amount,
                "time" => $time
            ));
            $this->_writeBalanceData($balance, $data);
        }
    }

   

    function removePurchase($balance, $thisUser, $name, $amount, $time)
    {
   
            $data = $this->_loadBalanceData($balance);

            foreach ($data as $user => $purchases) {
                if($user != $thisUser) {
                    continue;
                }
                
                for ($i = 0; $i < count($purchases); $i++) {
                    $purchase = $purchases[$i];
                    if ($purchase["name"] == $name && $purchase["amount"] == $amount && $purchase["time"] == $time) {
                        unset($data[$user][$i]);
                        $this->_writeBalanceData($balance, $data);
                        return TRUE;
                    }
                }
            }

            return FALSE;
    }

    function loadUserData($balance, $thisUser, $offset, $amount)
    {

        $data = $this->_loadBalanceData($balance);

        $ret = array();

        $index = 0;

        foreach ($data as $user => $purchases) {
            foreach ($purchases as $purchase) {
                $ret[$index] =  $purchase;
                $ret[$index]["edit"] = $user == $thisUser;
                $index++;
            }
        }

        usort($ret, function ($item1, $item2) {
            return $item2['time'] <=> $item1['time'];
        });

        return array_slice($ret, $offset, $amount);
    }

    function _loadBalanceData($balance)
    {
        $data = json_decode(file_get_contents($this->_dataDir . "/" . $balance . ".json"), true);
        return $data;
    }

    private function _writeBalanceData($balance, $data)
    {
        if (!file_exists($this->_filePath($balance))) {
            mkdir(dirname($this->_filePath($balance)), 0777, true);
        }

        file_put_contents($this->_filePath($balance), json_encode($data));
    }

    private function _filePath($balance)
    {
        return $this->_dataDir . "/" . $balance . ".json";
    }
}
