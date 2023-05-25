<?php
// Require
require_once('db.php');

// 상수
define('DAY_WIDTH', 20);

// 함수
function getMonthAbbreviation($monthNumber) {
    $monthNames = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Aug',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dec'
    ];

    return $monthNames[$monthNumber] ?? 'Invalid month';
}

// 변수 처리
$st = preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $_GET['st'] ?? '')? strtotime($_GET['st']): strtotime(date('Y-m-d 00:00:00', time() - 86400*7));
$ed = preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $_GET['ed'] ?? '')? strtotime($_GET['ed']): strtotime(date('Y-m-d 00:00:00', $st + 86400*14)) + 86399;
$nm = trim($_GET['nm'] ?? '');

// 헤더 표시
$header_month = [];
$header_day = [];
$cnt_day = 0;
$cnt_month_span = 0;
$curr_month = date('n', $st);
$frist_wd = date('w', $st);

for ($tt = $st; $tt <= $ed; $tt += 86400) {
    $day = date('j', $tt);
    $wd = date('w', $tt);
    $month = date('n', $tt);
    $cnt_day++;

    $header_day[] = '<td class="d' . $wd . '">' . $day . '</td>';

    if ($month != $curr_month) {
        $header_month[] = '<td colspan="' . $cnt_month_span . '">' . getMonthAbbreviation($curr_month) . '</td>';
        $curr_month = $month;
        $cnt_month_span = 0;
    }
    
    $cnt_month_span++;
}

$header_month[] = '<td colspan="' . $cnt_month_span . '">' . getMonthAbbreviation($curr_month) . '</td>';

// 이슈 가져오기
$issues = [];

$res = $db->query('
    SELECT periods.*, issues.title 
    FROM periods 
        LEFT JOIN issues ON periods.k = issues.k 
    WHERE
        periods.started_at <= "' . date('Y-m-d H:i:s', $ed) . '"
        AND periods.ended_at >= "' . date('Y-m-d H:i:s', $st) . '"
        ' . ($nm? 'AND assignee = "' . $db->real_escape_string($nm) . '"': '') . '
    ORDER BY
        DATE(IFNULL(periods.ended_at, periods.started_at)) ASC,
        periods.started_at ASC
');

while($v = $res->fetch_object()) {
    $dt_st = strtotime(substr($v->started_at, 0, 10));
    $dt_ed = strtotime(substr($v->ended_at, 0, 10));

    $issues[] = [
        'code' => $v->k,
        'title' => $v->title,
        'assignee' => $v->assignee,
        'cell' => max(0, ($dt_st - $st) / 86400),
        'left' => max(-1, (strtotime(substr($v->started_at, 0, 10)) - $st) / 86400 * (DAY_WIDTH + 3) - 2),
        'width' => (($dt_ed - max($st, $dt_st)) / 86400 + 1) * (DAY_WIDTH + 3)
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>JIRA Job Collector</title>
<meta charset="UTF-8">
<link rel="stylesheet" href="index.css" type="text/css" />
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" />
<script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js" integrity="sha256-eTyxS0rkjpLEo16uXTS0uVCS4815lc40K2iVpWDvdSY=" crossorigin="anonymous"></script>
<script src="index.js" type="text/javascript"></script>
</head>
<body>
<header>
    <form>
        From <input name="st" class="dt" type="text" value="<?php echo date('Y-m-d', $st) ?>" id="datepicker_st" />
        To <input name="ed" class="dt" type="text" value="<?php echo date('Y-m-d', $ed) ?>" id="datepicker_ed" />
        <input name="nm" type="text" value="<?php echo $nm ?>" placeholder="Assignee" />
        <input type="submit" value="Search" />
    </form>
</header>
<table id="tbl_gannt">
    <thead>
        <tr>
            <th rowspan="2">Code</th>
            <th rowspan="2">Issue Name</th>
            <th rowspan="2">Assignee</th>
            <?php echo implode('', $header_month) ?>
        </tr>
        <tr><?php echo implode('', $header_day) ?></tr>
    </thead>
    <tbody>
        <?php
        foreach($issues as $v) {
            $gantt = '';
            $wd = $frist_wd;

            for($i=0; $i < $cnt_day; $i++) {
                if($v['cell'] == $i) {
                    $gantt .= '<td class="d' . $wd . '"><div class="bar" style="width: ' . ($v['width']) . 'px"></div></td>';
                } else {
                    $gantt .= '<td class="d' . $wd . '"></td>';
                }
    
                $wd = ($wd + 1) % 7;
            }

            echo '<tr><th>' . $v['code'] . '</th><th>' . $v['title'] . '</th><th>' . $v['assignee'] . '</th>' . $gantt . '</tr>';
        }
        ?>
    </tbody>    
</table>
</body>
</html>
