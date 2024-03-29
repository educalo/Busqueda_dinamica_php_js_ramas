<?php


require 'config.php';

// Columnas a mostrar en la tabla
$columns = ['no_emp', 'nombre', 'apellido', 'fecha_nacimiento', 'fecha_ingreso'];

// Nombre de la tabla
$table = "empleados";

// Clave principal de la tabla
$id = 'no_emp';

// Campo a buscar
//real_escape_string para evitar codigo malicioso
//isset si existe este campo que se pasa como parametro
//operación ternaria
$campo = isset($_POST['campo']) ? $conn->real_escape_string($_POST['campo']) : null;

// Filtrado
$where = '';

if ($campo != null) {
    $where = "WHERE (";

    $cont = count($columns);
    for ($i = 0; $i < $cont; $i++) {
        $where .= $columns[$i] . " LIKE '%" . $campo . "%' OR ";
    }
    //para quitar el ultimo OR
    $where = substr_replace($where, "", -3);
    $where .= ")";
}

// Limites para la paginación predefinido es 10
$limit = isset($_POST['registros']) ? $conn->real_escape_string($_POST['registros']) : 10;
$pagina = isset($_POST['pagina']) ? $conn->real_escape_string($_POST['pagina']) : 0;

if (!$pagina) {
    $inicio = 0;
    $pagina = 1;
} else {
    $inicio = ($pagina - 1) * $limit;
}

$sLimit = "LIMIT $inicio , $limit";

// Ordenamiento

$sOrder = "";
if (isset($_POST['orderCol'])) {
    $orderCol = $_POST['orderCol'];
    $oderType = isset($_POST['orderType']) ? $_POST['orderType'] : 'asc';
    //intval para extraer el valor entero
    $sOrder = "ORDER BY " . $columns[intval($orderCol)] . ' ' . $oderType;
}

// Consulta
//implode convierte un arreglo a un string
//Se obtiene el número total de filas, se puede utilziar select count(*)
$sql = "SELECT SQL_CALC_FOUND_ROWS " . implode(", ", $columns) . "
FROM $table
$where
$sOrder
$sLimit";
$resultado = $conn->query($sql);
//cuantas filas nos trae la consulta
$num_rows = $resultado->num_rows;

// Consulta para total de registro filtrados
$sqlFiltro = "SELECT FOUND_ROWS()";
$resFiltro = $conn->query($sqlFiltro);
$row_filtro = $resFiltro->fetch_array();
$totalFiltro = $row_filtro[0];

// Consulta para total de registro
$sqlTotal = "SELECT count($id) FROM $table ";
$resTotal = $conn->query($sqlTotal);
$row_total = $resTotal->fetch_array();
$totalRegistros = $row_total[0];

// Mostrado resultados array output
$output = [];
$output['totalRegistros'] = $totalRegistros;
$output['totalFiltro'] = $totalFiltro;
$output['data'] = '';
$output['paginacion'] = '';

//fetch_assoc Obtener una fila de resultado como un array asociativo
if ($num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        $output['data'] .= '<tr>';
        $output['data'] .= '<td>' . $row['no_emp'] . '</td>';
        $output['data'] .= '<td>' . $row['nombre'] . '</td>';
        $output['data'] .= '<td>' . $row['apellido'] . '</td>';
        $output['data'] .= '<td>' . $row['fecha_nacimiento'] . '</td>';
        $output['data'] .= '<td>' . $row['fecha_ingreso'] . '</td>';
        $output['data'] .= '<td><a class="btn btn-warning btn-sm" href="editar.php?id=' . $row['no_emp'] . '">Editar</a></td>';
        $output['data'] .= "<td><a class='btn btn-danger btn-sm' href='elimiar.php?id=" . $row['no_emp'] . "'>Eliminar</a></td>";
        $output['data'] .= '</tr>';
    }
} else {
    $output['data'] .= '<tr>';
    $output['data'] .= '<td colspan="7">Sin resultados</td>';
    $output['data'] .= '</tr>';
}

// Paginación
//ceil redondea hacia arriba entero
if ($totalRegistros > 0) {
    $totalPaginas = ceil($totalFiltro / $limit);
    //.= concatenar
    $output['paginacion'] .= '<nav>';
    $output['paginacion'] .= '<ul class="pagination">';

    //4 enlaces de antes del enlace de pagina actual y 4 enlaces de pagina siguiente
    $numeroInicio = max(1, $pagina - 4);

    $numeroFin = min($totalPaginas, $numeroInicio + 9);

    for ($i = $numeroInicio; $i <= $numeroFin; $i++) {
        $output['paginacion'] .= '<li class="page-item' . ($pagina == $i ? ' active' : '') . '">';
        $output['paginacion'] .= '<a class="page-link" href="#" onclick="nextPage(' . $i . ')">' . $i . '</a>';
        $output['paginacion'] .= '</li>';
    }

    $output['paginacion'] .= '</ul>';
    $output['paginacion'] .= '</nav>';
}

//JSON_UNESCAPED_UNICODE para que reconozca los caracteres especiales ñ....
echo json_encode($output, JSON_UNESCAPED_UNICODE);
