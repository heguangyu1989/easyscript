<?php 
/**
 * @abstract make mysql db model
 * @author heguangyu
 */

$dbinfo = array();
$dbinfo['host'] = 'localhost';
$dbinfo['user'] = 'root';
$dbinfo['password'] = 'root';
$dbinfo['db_name'] = 'test';
$dbinfo['table_name'] = '';

function deal_input()
{
    global $dbinfo;
    foreach($dbinfo as $key => $val)
    {

        fwrite(STDOUT, "please input $key ".($val?" [default $val] :":" [require] :"));
        if($tmp = trim(fgets(STDIN)))
            $dbinfo[$key] = $tmp;
    }
}

function deal_table()
{

    global $dbinfo;
    $link = mysqli_connect($dbinfo['host'], $dbinfo['user'], $dbinfo['password'], $dbinfo['db_name']) or die('connect fail '.mysqli_error());
    
    $columns  = array();
    $sql = 'show columns from '.$dbinfo['table_name'];
    $result = mysqli_query($link, $sql);
    if(!$result)
    {
        echo 'sql query error : '.$sql.' '.PHP_EOL;
        main();
        return false;
    }

    while($tmp = mysqli_fetch_assoc($result))
    {
        $columns[] = $tmp['Field'];
    }

    $filename = ucfirst($dbinfo['table_name']).'Model.php';
    $fp = fopen($filename, 'a+');
    $r = <<<AAA
<?php
/**
 * @abstract auto db model
 */
    
    class MODEL_NAME 
    {
    PHP_EOL
AAA;
    $r = str_replace('PHP_EOL', PHP_EOL, $r);
    fwrite($fp, str_replace('MODEL_NAME', ucfirst($dbinfo['table_name']).'Model', $r));
    $r2 = <<<BBB
        public \$col_COLUMN; PHP_EOL
BBB;
    $r2 = str_replace('PHP_EOL', PHP_EOL, $r2);
    foreach($columns as $val)
    {
        fwrite($fp, str_replace('COLUMN', $val, $r2));
    }

    fwrite($fp, '    }'.PHP_EOL.'?>');
}

function print_dbinfo()
{
    echo "print db info ".PHP_EOL;
    global $dbinfo;
    print_r($dbinfo);
}

function main()
{
    do
    {
        deal_input();
        print_dbinfo();
        deal_table();
    
    }while(false);
}

main();
