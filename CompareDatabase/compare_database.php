<?php
/**
 * @abstract compare database different 
 * @author heguangyu
 */
 
$dbinfo1 = array();
$dbinfo1['host'] = 'localhost';
$dbinfo1['user'] = 'root';
$dbinfo1['password'] = 'root';
$dbinfo1['db_name'] = 'test1';
$dbinfo1['desc'] = 'test1';

$dbinfo2 = array();
$dbinfo2['host'] = 'localhost';
$dbinfo2['user'] = 'root';
$dbinfo2['password'] = 'root';
$dbinfo2['db_name'] = 'test2';
$dbinfo2['desc'] = 'test2';

// set database info  
function deal_input()
{
    global $dbinfo1, $dbinfo2;
    echo "Input db info 1: ".PHP_EOL;
    foreach($dbinfo1 as $key => $val)
    {
        fwrite(STDOUT, "please input $key " . ($val?" [default $val] :":" [require] :"));
        if($tmp = trim(fgets(STDIN)))
            $dbinfo1[$key] = $tmp;
    }
    echo "Input db info 2: ".PHP_EOL;
    foreach($dbinfo2 as $key => $val)
    {
        fwrite(STDOUT, "please input $key " . ($val?" [default $val] :":" [require] :"));
        if($tmp = trim(fgets(STDIN)))
            $dbinfo2[$key] = $tmp;
    }
    echo "set db info ok!".PHP_EOL;
    print_r($dbinfo1);
    print_r($dbinfo2);
    fwrite(STDOUT, "press any to start compare...".PHP_EOL);
}
 
function output($str)
{
    echo $str.PHP_EOL;
}

// TODO add index compare 
function compare()
{
    global $dbinfo1, $dbinfo2;
    $link1 = mysqli_connect($dbinfo1['host'], $dbinfo1['user'], $dbinfo1['password'], $dbinfo1['db_name']) or die('connect fail '.mysqli_error());
    $link2 = mysqli_connect($dbinfo2['host'], $dbinfo2['user'], $dbinfo2['password'], $dbinfo2['db_name']) or die('connect fail '.mysqli_error());
	
    $name1 = $dbinfo1['desc'];
    $name2 = $dbinfo2['desc'];
	 
    $tables_all = array();
    $tbn1 = 'Tables_in_'.$dbinfo1['db_name'];
    $tbn2 = 'Tables_in_'.$dbinfo2['db_name'];
    $tables_tmp = mysqli_query($link1, 'show tables');
    $index = 1;
    while($tmp = mysqli_fetch_assoc($tables_tmp))
    { 
        $tables1[$tmp[$tbn1]] = $index++;
        $tables_all[$tmp[$tbn1]] = 1;
    }
	 
    $tables_tmp = mysqli_query($link2, 'show tables');
    $index = 1;
    while($tmp = mysqli_fetch_assoc($tables_tmp))
    {
        $tables2[$tmp[$tbn2]] = $index++;
        $tables_all[$tmp[$tbn2]] = 1;
    }

    foreach ($tables_all as $key => $val)
    {
        // check wheater table exits
        if(isset($tables1[$key]) && !isset($tables2[$key]))
        {
            output("db [$name2] miss table [$key]"); 
        }
        elseif (!isset($tables1[$key]) && isset($tables2[$key]))
        {
            output("db [$name1] miss table [$key]");
        }
        else
        {
            // check wheater value is the same 
            $tables_tmp = mysqli_query($link1, "show create table $key");
            $tmp = mysqli_fetch_assoc($tables_tmp);
            $preg = '/\`(.+?)\`/';
            preg_match_all($preg, $tmp['Create Table'], $match);
            $table_key1 = array_flip($match[1]);
            $tables_tmp = mysqli_query($link2, "show create table $key");
            $tmp = mysqli_fetch_assoc($tables_tmp);
            $preg = '/\`(.+?)\`/';
            preg_match_all($preg, $tmp['Create Table'], $match);
            $table_key2 = array_flip($match[1]);
            $nbsp = '    ';
            foreach ($table_key1 as $k => $v)
            {
                if(!isset($table_key2[$k]))
                {
                    output("$nbsp db [$name2] table [$key] miss key $k");
                }
            }
            
            foreach ($table_key2 as $k => $v)
            {
                if(!isset($table_key1[$k]))
                {
                    output("$nbsp db [$name1] table [$key] miss key $k");
                }
            }
        }
    }
}
 

deal_input();
$time = microtime(true);
compare();
$now = microtime(true);
echo "use time : " . ($now - $time).PHP_EOL;
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
