<?php
@require_once('config.php');
@header('Content-Type: application/json; charset=utf-8');
$mysql = new mysqli($config['host'], $config['user'], $config['pass'], $config['name']);
$mysql->set_charset("utf8");
if (isset($_SERVER['HTTP_ORIGIN'])) {
    @header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    @header('Access-Control-Allow-Credentials: true');
    @header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        @header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        @header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

if(isset($_GET['provinces']) && isset($_GET['type'])){
    try {
        $condition = " WHERE 1=1";
        if(!empty($_GET['type'])){
            $condition = " WHERE h.hospitaltype='".$mysql->real_escape_string($_GET['type'])."'";
        }
        if($result = $mysql->query("SELECT DISTINCT p.provinceid, p.name_th FROM hospitals h INNER JOIN subdistricts sd ON sd.subdistrictid=h.subdistrictid INNER JOIN districts d ON d.districtid=sd.districtid INNER JOIN provinces p ON p.provinceid=d.provinceid ".$condition." ORDER BY p.name_th")){
            $provinces = [];
            while($row = $result->fetch_assoc()){
                $provinces[] = $row;
            }
            $result->close();

            echo json_encode(['error'=>false, 'data'=>$provinces]);
        }else{
            throw new Exception('Failed to fetch data.');
        }
        
    }catch(Exception $e){
        echo json_encode(['error'=>true, 'msg'=>$e->getMessage()]);
    }
}elseif(isset($_GET['districts']) && !empty($_GET['pid'])) {
    try {
        if($result = $mysql->query("SELECT DISTINCT d.districtid, d.name_th FROM hospitals h INNER JOIN subdistricts sd ON sd.subdistrictid=h.subdistrictid INNER JOIN districts d ON d.districtid=sd.districtid INNER JOIN provinces p ON p.provinceid=d.provinceid WHERE p.provinceid=".intval($_GET['pid'])." ORDER BY d.name_th")){
            $districts = [];
            while($row = $result->fetch_assoc()){
                $districts[] = $row;
            }
            $result->close();

            echo json_encode(['error'=>false, 'data'=>$districts]);
        }else{
            throw new Exception('Failed to fetch data.');
        }
        
    }catch(Exception $e){
        echo json_encode(['error'=>true, 'msg'=>$e->getMessage()]);
    }
}elseif(isset($_GET['subdistricts']) && !empty($_GET['did'])) {
    try {
        if($result = $mysql->query("SELECT DISTINCT sd.subdistrictid, sd.name_th FROM hospitals h INNER JOIN subdistricts sd ON sd.subdistrictid=h.subdistrictid INNER JOIN districts d ON d.districtid=sd.districtid INNER JOIN provinces p ON p.provinceid=d.provinceid WHERE d.districtid=".intval($_GET['did'])." ORDER BY sd.name_th")){
            $subdistricts = [];
            while($row = $result->fetch_assoc()){
                $subdistricts[] = $row;
            }
            $result->close();

            echo json_encode(['error'=>false, 'data'=>$subdistricts]);
        }else{
            throw new Exception('Failed to fetch data.');
        }
        
    }catch(Exception $e){
        echo json_encode(['error'=>true, 'msg'=>$e->getMessage()]);
    }

}elseif(isset($_GET['hospitaltypes'])) {
    try {
        if($result = $mysql->query("SELECT DISTINCT h.hospitaltype FROM hospitals h ORDER by h.hospitaltype")){
            $types = [];
            while($row = $result->fetch_assoc()){
                $types[] = $row;
            }
            $result->close();

            echo json_encode(['error'=>false, 'data'=>$types]);
        }else{
            throw new Exception('Failed to fetch data.');
        }
        
    }catch(Exception $e){
        echo json_encode(['error'=>true, 'msg'=>$e->getMessage()]);
    }
    
}elseif(isset($_GET['search']) && !empty($_GET['q'])){

    try{
        if($result = $mysql->query("SELECT COUNT(h.hospitalid) num FROM hospitals h WHERE h.hospitalname LIKE '%".$mysql->real_escape_string($_GET['q'])."%'")){
            $row = $result->fetch_assoc();
            $total = $row['num'];
            $pages = ceil($total/$size);
            $result->close();

            $page = 1;
            if(!empty($_GET['page'])){
                $page = intval($_GET['page']);
            }
            if($page < 0){
                $page = 1;
            }elseif($page > $pages){
                $page = $pages;
            }
            $size = 10;
            if(!empty($_GET['size'])){
                $size = intval($_GET['size']);
            }

            $sort = " ORDER BY distance ASC";
            if(!empty($_GET['sort']) && $_GET['sort'] == "Nearest"){
                $sort = " ORDER BY distance ASC";
            }else if(!empty($_GET['sort']) && $_GET['sort'] == "A-Z"){
                $sort = " ORDER BY h.hospitalname ASC";
            }

            $result = $mysql->query("SELECT h.hospitalid, h.hospitaltype, ST_DISTANCE(h.location, GeomFromText('Point(".floatval($_GET['lon'])." ".floatval($_GET['lat']).")')) * 111.38 AS distance, h.hospitalname, h.address, h.hours, h.phone, h.linelink, h.lineid, h.email, h.website, h.note, ST_X(h.location) lon, ST_Y(h.location) lat FROM hospitals h WHERE h.hospitalname LIKE '%".$mysql->real_escape_string($_GET['q'])."%' ".$sort." LIMIT ".(($page - 1)*$size).", ".$size);
            $rows = [];
            if($result !== false){
                while($row = $result->fetch_assoc()){
                    $rows[] = $row;
                }
                $result->close();
            }

            echo json_encode(['error'=>false, 'data'=>['total'=>$total, 'pages'=>$pages, 'rows'=>$rows]]);
        }else{
            echo json_encode(['error'=>false, 'data'=>['total'=>0, 'pages'=>0, 'rows'=>[]]]);
        }

    }catch(Exception $e){
        echo json_encode(['error'=>true, 'msg'=>$e->getMessage()]);
    }

}elseif(isset($_GET['allhospitals']) && !empty($_GET['lat']) && !empty($_GET['lon'])){

    try{
        if($result = $mysql->query("SELECT h.hospitalid, h.hospitaltype, ST_DISTANCE(h.location, GeomFromText('Point(".floatval($_GET['lon'])." ".floatval($_GET['lat']).")')) * 111.38 AS distance, h.hospitalname, h.address, h.hours, h.phone, h.linelink, h.lineid, h.email, h.website, h.note, ST_X(h.location) lon, ST_Y(h.location) lat FROM hospitals h ORDER BY distance")){
            $rows = [];
            while($row = $result->fetch_assoc()){
                $rows[] = $row;
            }
            $result->close();

            echo json_encode(['error'=>false, 'data'=>$rows]);
        }else{
            throw new Exception('Failed to fetch data.');
        }

    }catch(Exception $e){
        echo json_encode(['error'=>true, 'msg'=>$e->getMessage()]);
    }

}elseif(isset($_GET['hospitals']) && !empty($_GET['lat']) && !empty($_GET['lon'])) {
    try {

        $page = 1;
        if(!empty($_GET['page'])){
            $page = intval($_GET['page']);
        }

        $sort = " ORDER BY distance ASC";
        if(!empty($_GET['sort']) && $_GET['sort'] == "Nearest"){
            $sort = " ORDER BY distance ASC";
        }else if(!empty($_GET['sort']) && $_GET['sort'] == "A-Z"){
            $sort = " ORDER BY h.hospitalname ASC";
        }
        $size = 10;
        if(!empty($_GET['size'])){
            $size = intval($_GET['size']);
        }

        if(!empty($_GET['type']) && $result = $mysql->query("SELECT COUNT(h.hospitalid) num FROM hospitals h WHERE h.hospitaltype='".$mysql->real_escape_string($_GET['type'])."'")){
            $row = $result->fetch_assoc();
            $total = $row['num'];
            $pages = ceil($total/$size);
            $result->close();
            
            if($page < 0){
                $page = 1;
            }elseif($page > $pages){
                $page = $pages;
            }

            $result = $mysql->query("SELECT h.hospitalid, h.hospitaltype, ST_DISTANCE(h.location, GeomFromText('Point(".floatval($_GET['lon'])." ".floatval($_GET['lat']).")')) * 111.38 AS distance, h.hospitalname, h.address, h.hours, h.phone, h.linelink, h.lineid, h.email, h.website, h.note, ST_X(h.location) lon, ST_Y(h.location) lat FROM hospitals h INNER JOIN subdistricts sd ON sd.subdistrictid=h.subdistrictid INNER JOIN districts d ON d.districtid=sd.districtid INNER JOIN provinces p ON p.provinceid=d.provinceid WHERE h.hospitaltype='".$mysql->real_escape_string($_GET['type'])."' ".$sort." LIMIT ".(($page - 1)*$size).", ".$size);
            $rows = [];
            while($row = $result->fetch_assoc()){
                $rows[] = $row;
            }
            $result->close();

            echo json_encode(['error'=>false, 'data'=>['total'=>$total, 'pages'=>$pages, 'rows'=>$rows]]);

        }elseif(!empty($_GET['pid']) && $result = $mysql->query("SELECT COUNT(h.hospitalid) num FROM hospitals h INNER JOIN subdistricts sd ON sd.subdistrictid=h.subdistrictid INNER JOIN districts d ON d.districtid=sd.districtid INNER JOIN provinces p ON p.provinceid=d.provinceid WHERE p.provinceid=".intval($_GET['pid']))){
            $row = $result->fetch_assoc();
            $total = $row['num'];
            $pages = ceil($total/$size);
            $result->close();

            if($page < 0){
                $page = 1;
            }elseif($page > $pages){
                $page = $pages;
            }


            $result = $mysql->query("SELECT h.hospitalid, h.hospitaltype, ST_DISTANCE(h.location, GeomFromText('Point(".floatval($_GET['lon'])." ".floatval($_GET['lat']).")')) * 111.38 AS distance, h.hospitalname, h.address, h.hours, h.phone, h.linelink, h.lineid, h.email, h.website, h.note, ST_X(h.location) lon, ST_Y(h.location) lat FROM hospitals h INNER JOIN subdistricts sd ON sd.subdistrictid=h.subdistrictid INNER JOIN districts d ON d.districtid=sd.districtid INNER JOIN provinces p ON p.provinceid=d.provinceid WHERE p.provinceid=".intval($_GET['pid'])." ".$sort." LIMIT ".(($page - 1)*$size).", ".$size);
            $rows = [];
            while($row = $result->fetch_assoc()){
                $rows[] = $row;
            }
            $result->close();

            echo json_encode(['error'=>false, 'data'=>['total'=>$total, 'pages'=>$pages, 'rows'=>$rows]]);
        
        }elseif(!empty($_GET['did']) && $result = $mysql->query("SELECT COUNT(h.hospitalid) num FROM hospitals h INNER JOIN subdistricts sd ON sd.subdistrictid=h.subdistrictid INNER JOIN districts d ON d.districtid=sd.districtid INNER JOIN provinces p ON p.provinceid=d.provinceid WHERE d.districtid=".intval($_GET['did']))){
            $row = $result->fetch_assoc();
            $total = $row['num'];
            $pages = ceil($total/$size);
            $result->close();
            
            if($page < 0){
                $page = 1;
            }elseif($page > $pages){
                $page = $pages;
            }


            $result = $mysql->query("SELECT h.hospitalid, h.hospitaltype, ST_DISTANCE(h.location, GeomFromText('Point(".floatval($_GET['lon'])." ".floatval($_GET['lat']).")')) * 111.38 AS distance, h.hospitalname, h.address, h.hours, h.phone, h.linelink, h.lineid, h.email, h.website, h.note, ST_X(h.location) lon, ST_Y(h.location) lat FROM hospitals h INNER JOIN subdistricts sd ON sd.subdistrictid=h.subdistrictid INNER JOIN districts d ON d.districtid=sd.districtid INNER JOIN provinces p ON p.provinceid=d.provinceid WHERE d.districtid=".intval($_GET['did'])." ".$sort." LIMIT ".(($page - 1)*$size).", ".$size);
            $rows = [];
            while($row = $result->fetch_assoc()){
                $rows[] = $row;
            }
            $result->close();

            echo json_encode(['error'=>false, 'data'=>['total'=>$total, 'pages'=>$pages, 'rows'=>$rows]]);
        
        }elseif(!empty($_GET['sid']) && $result = $mysql->query("SELECT COUNT(h.hospitalid) num FROM hospitals h INNER JOIN subdistricts sd ON sd.subdistrictid=h.subdistrictid INNER JOIN districts d ON d.districtid=sd.districtid INNER JOIN provinces p ON p.provinceid=d.provinceid WHERE sd.subdistrictid=".intval($_GET['sid']))){
            $row = $result->fetch_assoc();
            $total = $row['num'];
            $pages = ceil($total/$size);
            $result->close();
            
            if($page < 0){
                $page = 1;
            }elseif($page > $pages){
                $page = $pages;
            }


            $result = $mysql->query("SELECT h.hospitalid, h.hospitaltype, ST_DISTANCE(h.location, GeomFromText('Point(".floatval($_GET['lon'])." ".floatval($_GET['lat']).")')) * 111.38 AS distance, h.hospitalname, h.address, h.hours, h.phone, h.linelink, h.lineid, h.email, h.website, h.note, ST_X(h.location) lon, ST_Y(h.location) lat FROM hospitals h INNER JOIN subdistricts sd ON sd.subdistrictid=h.subdistrictid INNER JOIN districts d ON d.districtid=sd.districtid INNER JOIN provinces p ON p.provinceid=d.provinceid WHERE sd.subdistrictid=".intval($_GET['sid'])." ".$sort." LIMIT ".(($page - 1)*$size).", ".$size);
            $rows = [];
            while($row = $result->fetch_assoc()){
                $rows[] = $row;
            }
            $result->close();

            echo json_encode(['error'=>false, 'data'=>['total'=>$total, 'pages'=>$pages, 'rows'=>$rows]]);
            
        }elseif($result = $mysql->query("SELECT COUNT(h.hospitalid) num FROM hospitals h")){
            $row = $result->fetch_assoc();
            $total = $row['num'];
            $pages = ceil($total/$size);
            $result->close();
            
            if($page < 0){
                $page = 1;
            }elseif($page > $pages){
                $page = $pages;
            }

            $result = $mysql->query("SELECT h.hospitalid, h.hospitaltype, ST_DISTANCE(h.location, GeomFromText('Point(".floatval($_GET['lon'])." ".floatval($_GET['lat']).")')) * 111.38 AS distance, h.hospitalname, h.address, h.hours, h.phone, h.linelink, h.lineid, h.email, h.website, h.note, ST_X(h.location) lon, ST_Y(h.location) lat FROM hospitals h ".$sort." LIMIT ".(($page - 1)*$size).", ".$size);
            $rows = [];
            while($row = $result->fetch_assoc()){
                $rows[] = $row;
            }
            $result->close();

            echo json_encode(['error'=>false, 'data'=>['total'=>$total, 'pages'=>$pages, 'rows'=>$rows]]);
        }else{
            throw new Exception('Failed to fetch data.');
        }
        
    }catch(Exception $e){
        echo json_encode(['error'=>true, 'msg'=>$e->getMessage()]);
    }
}
?>