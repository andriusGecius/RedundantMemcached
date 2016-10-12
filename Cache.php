<?php

namespace RedundantMemcached;

use \Memcached;

class Cache
{
    public function __construct($config)
    {

        $this->serverIP1 = $config['memcached1_ip'];
        $this->serverPort1 = $config['memcached1_port'];
        $this->serverIP2 = $config['memcached2_ip'];
        $this->serverPort2 = $config['memcached2_port'];

        $this->memc1 = new \Memcached;
        $this->memc1->addServer($this->serverIP1, $this->serverPort1);
        $this->memc1->setOption(Memcached::OPT_CONNECT_TIMEOUT, 100);

        $this->memc2 = new \Memcached;
        $this->memc2->addServer($this->serverIP2, $this->serverPort2);
        $this->memc2->setOption(Memcached::OPT_CONNECT_TIMEOUT, 100);
    }

    public function set($hash, $value, $expires)
    {
        $this->memc1->set($hash, $value, $expires);
        $this->memc2->set($hash, $value, $expires);
    }


    public function delete($hash)
    {
        $this->memc1->delete($hash);
        $this->memc2->delete($hash);
    }

    public function get($hash)
    {
        $stats1 = $this->memc1->getStats();
        $stats2 = $this->memc2->getStats();

        //The connection is alive if there is a PID assigned
        $status1 = ($stats1[$this->serverIP1.':'.$this->serverPort1]['pid'] > 0) ? 1 : 0;
        $status2 = ($stats2[$this->serverIP2.':'.$this->serverPort2]['pid'] > 0) ? 1 : 0;

        $get1 = $this->memc1->get($hash);
        $get2 = $this->memc2->get($hash);

        //Both servers are up and return the same value
        if($status1 == 1 && $status2 == 1 && $get1 === $get2 && isset($get1)) {
            return $get1;

        //Both servers are up, but returned values are different
        } else if($status1 == 1 && $status2 == 1 && $get1 !== $get2) {
            $this->delete($hash);

            return null;

        //Only the 1st server is alive
        } else if($status1 == 1 && $status2 != 1) {
            return $get1;

        //Only the 2nd server is alive
        } else if($status1 != 1 && $status2 == 1) {
            return $get2;
        } else {
            return null;
        }
    }

}

?>
