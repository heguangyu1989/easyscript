<?php
function format_version($output)
{
    $res = array();
    $res = explode('|', $output);
    $res['r'] = intval(str_replace('r', '', trim($res['0'])));
    unset($res[0]);
    $res['author'] = trim($res['1']);
    unset($res[1]);
    $res['date'] = substr(trim($res['2']), 0, 19);
    unset($res[2]);
    $res['time'] = strtotime($res['date']);
    return $res;
}

function draw_date_img($data, $name)
{
    $h = 720;
    $w = 1024;
    $img = imagecreate($w, $h);
    imagecolorallocate($img, 255, 255, 255);//设置底色
    $line_color = imagecolorallocate($img,  0, 0, 0);
    $textcolor = imagecolorallocate($img,  0, 255, 0);
    $side = 50;
    imageline($img, $side, $side, $side, $h - $side, $line_color);
    imageline($img, $side, $h - $side, $w - $side, $h - $side, $line_color);
    $h_step = floor(($h  - 2*$side) / max($data));
    for ($i = 1; $i <= max($data); $i++)
    {
        $tmp_h = $h - $side - $h_step * $i;
        imagestring($img, 5, $side, $tmp_h, $i, $textcolor);
    }
    $last_point = array($side, $h - $side);
    $index = 1;
    $w_step = floor(($w - $side * 2) / count($data));
    $line_color2 = imagecolorallocate($img,  255, 0, 0);
    foreach ($data as $key => $val)
    {
        $x = $side + $w_step * $index;
        $y = $h - $side - $h_step * $val;
        imageline($img, $last_point[0], $last_point[1], $x, $y, $line_color2);
        $last_point = array($x, $y);
        $index++;
        for($i = 0; $i < 10; $i++)
        {
            if($i  < 4) $x_offset = -10;
            else $x_offset = 0;
            imagestring($img, 5, $x + $x_offset, $h - $side - 10 + ($i % 5) * 10, $key[$i], $textcolor);
        }
       }
    imagepng($img, $name.'.png');
}

// 0.set start time and endtime
$start_time = mktime(0, 0, 0, 7, 26, 2016);
$end_time = mktime(0, 0, 0, 8, 20, 2016);
if($start_time + 86400 > $end_time)
{
    exit("start time and end time must more than one day means 86400s");
}

// 1.update svn 
// exec('svn update  ~/svn_heguangyu', $output);

// 2.get start & end version info
$output = array();
exec('svn log -l 1 -q  ~/svn_heguangyu', $output);
$lastinfo = format_version($output[1]);

$startinfo = array();
$endinfo = array();
$go_flag = 0;
$last_r = $lastinfo['r'];
$step = 1000;
do
{
    $start_r = $last_r - $step;
    $cmd = "svn log -r $start_r:$last_r -q ~/svn_heguangyu";
    $last_r = $start_r;
    echo "exec : ".$cmd.PHP_EOL;
    $output = array();
    exec($cmd, $output);
    if($output)
    {
        for($i=0; $i<$step; $i++)
        {
            $fisrt = 2 * $i + 1;
            $second = 2 * ($i + 1) + 1;
            $fisrt_arr = format_version($output[$fisrt]);
            $second_arr = format_version($output[$second]);
            if($fisrt_arr['time'] <= $start_time && $start_time <= $second_arr['time'])
            {
                $startinfo = $fisrt_arr;
                $go_flag += 1;
            }
             if($fisrt_arr['time'] <= $end_time && $end_time <= $second_arr['time'])
            {
                $endinfo = $second_arr;
                $go_flag += 1;
            }
        }
    }
}while ($go_flag < 2 && $output);

if(!$startinfo && !$endinfo)
{
    exit("can not get start & end info");
}

// 3.分析日志
$cmd = "svn log -r ".$startinfo['r'].":".$endinfo['r']." -v --xml ~/svn_heguangyu";
echo "exec : ".$cmd.PHP_EOL;
$output = array();
exec($cmd, $output);
$xml = implode($output);
$xml_arr = simplexml_load_string($xml);
$res = array();
$log_users = array('zhaocheng'=>1);
foreach ($xml_arr as $key => $val)
{
    $data = (array)$val;
    if(!isset($log_users[$data['author']]) || $log_users[$data['author']] != 1)
    {
        continue;
    }
    $res[$data['author']]['svn_update_count'] += 1; 
    $res[$data['author']]['dates'][substr($data['date'], 0, 10)] += 1; 
    $paths = (array)$data['paths'];
    foreach ($paths as $k=>$v)
    {
        if(!isset($res[$data['author']]['updatefiles'][$v]))
        {
            $res[$data['author']]['updatefiles']['count'] += 1;
            $res[$data['author']]['updatefiles'][$v] = 1;
        }
        else
        {
            $res[$data['author']]['updatefiles'][$v] += 1;
        }
    }
    $parten = '/\#\d+/i';
    preg_match_all($parten, $data['msg'].$data['msg'], $match);
    foreach ($match[0] as $redmine)
    {
         $res[$data['author']]['redmine'][$redmine] += 1; 
    }
}
draw_date_img($res['zhaocheng']['dates'], 'zhaocheng_dailyupdate');
print_r($res);



