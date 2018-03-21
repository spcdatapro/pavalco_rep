<?php
set_time_limit(0);
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\App;
$app->get('/hello', function (Request $request, Response $response) {

    try{
        $db = new dbpavalco();
        //$name = $request->getAttribute('name');
        $response->getBody()->write("Done");
    }catch(Exception $err){
        $response->getBody()->write("ERROR: ".$err->getCode()." - ".$err->getMessage());
    }

    return $response;
});

function orderByAcumulado($a, $b){ return $a['Acumulado'] == $b['Acumulado'] ? 0 : ($a['Acumulado'] < $b['Acumulado'] ? 1 : -1); }

function rdt($total, $parcial){ return $total > 0 ? round($parcial * 100 / $total, 2) : 0.00 ; }

function secondsToTime($s){
    $h = floor($s / 3600);
    $s -= $h * 3600;
    $m = floor($s / 60);
    $s -= $m * 60;
    return $h.':'.sprintf('%02d', $m).':'.sprintf('%02d', $s);
}

$app->get('/rptintegra/{anio}/{dmes}/{ames}/{tten}', function(Request $request, Response $response){
    $ini = microtime(true);

    $anio = (int)$request->getAttribute('anio');
    $dmes = (int)$request->getAttribute('dmes');
    $ames = (int)$request->getAttribute('ames');
    $tten = (int)$request->getAttribute('tten');
    //$dmes = 1; $ames = 3; $anio = 2016; $tten = 1;

    $mes = [1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR', 5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO', 9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC'];

    $db = new dbpavalco();
    //$tt = "TOP 5";
    $tt = "";
    $query = "SELECT COUNT(*) AS totMaquinasActivas FROM Ubicaciones WHERE DeBaja = 0";
    $totMaquinasActivas = $db->getQuery($query)[0]['totMaquinasActivas'];
    $data['totMaquinasActivas'] = $totMaquinasActivas;
    $query = "SELECT $tt Id, DescTipoLlamada AS TipoLlamada, 0 AS Acumulado, 0.00 AS MIX_ACUM FROM TiposLlamada";
    $data['TiposLlamada'] = $db->getQuery($query);
    $query = "SELECT $tt a.Id, CONCAT(b.DescFuenteProblema, ' - ', a.DescTipoProblema) AS TipoProblema, 0 AS Acumulado, 0.00 AS MIX_ACUM ";
    $query.= "FROM TiposProblema a INNER JOIN FuentesProblema b ON b.id = a.TipoProblema_FuenteProblema WHERE a.Mostrar = 1";
    $data['TiposProblema'] = $db->getQuery($query);
    $query = "SELECT $tt Id, DescUbicacion AS Ubicacion, 0 AS Acumulado, 0.00 AS MIX_ACUM FROM Ubicaciones WHERE DeBaja = 0";
    $data['Ubicaciones'] = $db->getQuery($query);

    for($i = $dmes; $i <= $ames; $i++){

        $cnt = 0;
        foreach($data['TiposLlamada'] as $tipo){
            $query = "SELECT COUNT(a.id) AS conteo FROM Problemas a INNER JOIN TiposLlamada b ON b.id = a.TipoLlamada_Problema ";
            $query.= "WHERE YEAR(a.FHCierre) = $anio AND MONTH(a.FHCierre) = $i AND b.id = ".$tipo['Id']." ";
			$query.= "AND a.Problema_Equipo = 2 ";
			$query.= "GROUP BY b.id";
            //echo $query.'<br/><br/>';
            $res = $db->getQuery($query);
            $data['TiposLlamada'][$cnt][$mes[$i]] = $res == false ? 0 : $res[0]['conteo'];
            $data['TiposLlamada'][$cnt]['Mix_'.$mes[$i]] = 0;
            $data['TiposLlamada'][$cnt]['Acumulado'] += $data['TiposLlamada'][$cnt][$mes[$i]];
            $cnt++;
        }


        $cnt = 0;
        foreach($data['TiposProblema'] as $tipo){
            $query = "SELECT COUNT(a.id) AS conteo FROM Problemas a INNER JOIN TiposProblema b ON b.id = a.Problema_TipoProblema ";
            $query.= "WHERE YEAR(a.FHCierre) = $anio AND MONTH(a.FHCierre) = $i AND b.id = ".$tipo['Id']." ";
			$query.= "AND a.Problema_Equipo = 2 ";
			$query.= "GROUP BY b.id";
            //echo $query.'<br/><br/>';
            $res = $db->getQuery($query);
            $data['TiposProblema'][$cnt][$mes[$i]] = $res == false ? 0 : $res[0]['conteo'];
            $data['TiposProblema'][$cnt]['Mix_'.$mes[$i]] = 0;
            $data['TiposProblema'][$cnt]['Acumulado'] += $data['TiposProblema'][$cnt][$mes[$i]];
            $cnt++;
        }

        $cnt = 0;
        foreach($data['Ubicaciones'] as $ubica){
            $query = "SELECT COUNT(a.id) AS conteo FROM Problemas a INNER JOIN Ubicaciones b ON b.id = a.Problema_Ubicacion ";
            $query.= "WHERE YEAR(a.FHCierre) = $anio AND MONTH(a.FHCierre) = $i AND b.DeBaja = 0 AND b.id = ".$ubica['Id']." ";
			$query.= "AND a.Problema_Equipo = 2 ";
			$query.= "GROUP BY b.id";
            //echo $query.'<br/><br/>';
            $res = $db->getQuery($query);
            $data['Ubicaciones'][$cnt][$mes[$i]] = $res == false ? 0 : $res[0]['conteo'];
            $data['Ubicaciones'][$cnt]['Mix_'.$mes[$i]] = 0;
            $data['Ubicaciones'][$cnt]['Acumulado'] += $data['Ubicaciones'][$cnt][$mes[$i]];
            $cnt++;
        }

    }

    usort($data['TiposLlamada'], "orderByAcumulado");
    usort($data['TiposProblema'], "orderByAcumulado");
    usort($data['Ubicaciones'], "orderByAcumulado");

    //Si solo pide el top 10
    if($tten == 1){
        $data['TiposLlamada'] = array_slice($data['TiposLlamada'], 0 , 10, true);
        $data['TiposProblema'] = array_slice($data['TiposProblema'], 0 , 10, true);
        $data['Ubicaciones'] = array_slice($data['Ubicaciones'], 0 , 10, true);
    }


    //Calculo de totales
    $totTipoLlamada = ['Id' => '', 'TipoLlamada' => '', 'Acumulado' => 0, 'MIX_ACUM' => ''];
    $totTipoProblema = ['Id' => '', 'TipoProblema' => '', 'Acumulado' => 0, 'MIX_ACUM' => ''];
    $totUbicacion = ['Id' => '', 'Ubicacion' => '', 'Acumulado' => 0, 'MIX_ACUM' => ''];
    for($i = $dmes; $i <= $ames; $i++){

        $cnt = 0;
        $totTipoLlamada[$mes[$i]] = 0;
        foreach($data['TiposLlamada'] as $tipo){
            $totTipoLlamada[$mes[$i]] += $data['TiposLlamada'][$cnt][$mes[$i]];
            $totTipoLlamada['Mix_'.$mes[$i]] = '';
            $cnt++;
        }


        $cnt = 0;
        $totTipoProblema[$mes[$i]] = 0;
        foreach($data['TiposProblema'] as $tipo){
            $totTipoProblema[$mes[$i]] += $data['TiposProblema'][$cnt][$mes[$i]];
            $totTipoProblema['Mix_'.$mes[$i]] = '';
            $cnt++;
        }

        $cnt = 0;
        $totUbicacion[$mes[$i]] = 0;
        foreach($data['Ubicaciones'] as $ubica){
            $totUbicacion[$mes[$i]] += $data['Ubicaciones'][$cnt][$mes[$i]];
            $totUbicacion['Mix_'.$mes[$i]] = '';
            $cnt++;
        }

    }



    $sumAcum = ['TiposLlamada' => 0, 'TiposProblema' => 0, 'Ubicaciones' => 0];
    for($i = $dmes; $i <= $ames; $i++){
        $sumAcum['TiposLlamada'] += $totTipoLlamada[$mes[$i]];
        $sumAcum['TiposProblema'] += $totTipoProblema[$mes[$i]];
        $sumAcum['Ubicaciones'] += $totUbicacion[$mes[$i]];
    }

    $totTipoLlamada['Acumulado'] = $sumAcum['TiposLlamada'];
    $totTipoProblema['Acumulado'] = $sumAcum['TiposProblema'];
    $totUbicacion['Acumulado'] = $sumAcum['Ubicaciones'];

    array_push($data['TiposLlamada'], $totTipoLlamada);
    array_push($data['TiposProblema'], $totTipoProblema);
    array_push($data['Ubicaciones'], $totUbicacion);

    //Calculo de los campos MIX
    for($i = $dmes; $i <= $ames; $i++){
        $cnt = 0;
        foreach($data['TiposLlamada'] as $tipo){
            if((int)$tipo['Id'] > 0){
                $data['TiposLlamada'][$cnt]['Mix_'.$mes[$i]] = rdt($totTipoLlamada[$mes[$i]], $data['TiposLlamada'][$cnt][$mes[$i]]);
            }
            $cnt++;
        }

        $cnt = 0;
        foreach($data['TiposProblema'] as $tipo){
            if((int)$tipo['Id'] > 0){
                $data['TiposProblema'][$cnt]['Mix_'.$mes[$i]] = rdt($totTipoProblema[$mes[$i]], $data['TiposProblema'][$cnt][$mes[$i]]);
            }
            $cnt++;
        }

        $cnt = 0;
        foreach($data['Ubicaciones'] as $ubicacion){
            if((int)$ubicacion['Id'] > 0){
                $data['Ubicaciones'][$cnt]['Mix_'.$mes[$i]] = rdt($totUbicacion[$mes[$i]], $data['Ubicaciones'][$cnt][$mes[$i]]);
            }
            $cnt++;
        }
    }

    $cnt = 0;
    foreach($data['TiposLlamada'] as $tipo){
        if((int)$tipo['Id'] > 0){
            $data['TiposLlamada'][$cnt]['MIX_ACUM'] = rdt($totTipoLlamada['Acumulado'], $data['TiposLlamada'][$cnt]['Acumulado']);
        }
        $cnt++;
    }

    $cnt = 0;
    foreach($data['TiposProblema'] as $tipo){
        if((int)$tipo['Id'] > 0){
            $data['TiposProblema'][$cnt]['MIX_ACUM'] = rdt($totTipoProblema['Acumulado'], $data['TiposProblema'][$cnt]['Acumulado']);
        }
        $cnt++;
    }

    $cnt = 0;
    foreach($data['Ubicaciones'] as $ubicacion){
        if((int)$ubicacion['Id'] > 0){
            $data['Ubicaciones'][$cnt]['MIX_ACUM'] = rdt($totUbicacion['Acumulado'], $data['Ubicaciones'][$cnt]['Acumulado']);
        }
        $cnt++;
    }

    $fin = microtime(true);
    $data['Transcurrido'] = secondsToTime($fin - $ini);

    header('Content-Type: application/json');
    print json_encode($data);
});

$app->run();

