<?php
session_start();

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
} else {
  echo "Inicia Sesion para acceder a este contenido.<br>";
  echo "<br><a href='login.html'>Login</a>";
  echo "<br><br><a href='index.html'>Registrarme</a>";
  header('Location: login.html'); //redirige a la página de login si el usuario quiere ingresar sin iniciar sesion

  exit;
}

$now = time();

if ($now > $_SESSION['expire']) {
  session_destroy();
  header('Location: login.html'); //redirige a la página de login, modifica la url a tu conveniencia
  echo "Tu sesion ha expirado,
<a href='login.html'>Inicia Sesion</a>";
  exit;
}

$conexion = new mysqli("localhost", "root", "", "Comercializadora");

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$id = $nombre = $descripcion = $cant = "";
$modo_edicion = false;
$error = "";

// 🗑️ Si se solicitó eliminar
if (isset($_GET['eliminar'])) {
    $idEliminar = intval($_GET['eliminar']);
    $conexion->query("DELETE FROM productos WHERE id = $idEliminar");
    header("Location: ProductosEdit.php"); // Redirigir para evitar reenvío
    exit();
}

// Si se solicitó editar (cargar datos al formulario)
if (isset($_GET['editar'])) {
    $idEditar = intval($_GET['editar']);
    $res = $conexion->query("SELECT * FROM productos WHERE id = $idEditar");
    if ($res && $fila = $res->fetch_assoc()) {
        $id = $fila['id'];
        $nombre = $fila['nombre'];
        $descripcion = $fila['descripcion'];
        $cant = $fila['cant_disponible'];
        $modo_edicion = true;
    }
}

// Guardar (nuevo o editado)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $descripcion = $conexion->real_escape_string($_POST['descripcion']);
    $cant = intval($_POST['cant_disponible']);

    if (isset($_POST['guardar'])) {
        if (!$modo_edicion) {
            // Verificar si el ID ya existe
            $verificar = $conexion->query("SELECT id FROM productos WHERE id = $id");
            if ($verificar && $verificar->num_rows > 0) {
                $error = "Error: Este ID ya está registrado.";
            } else {
                $sql = "INSERT INTO productos (id, nombre, descripcion, cant_disponible)
                        VALUES ($id, '$nombre', '$descripcion', $cant)";
                if (!$conexion->query($sql)) {
                    $error = "Error al guardar: " . $conexion->error;
                } else {
                    header("Location: ProductosEdit.php");
                    exit();
                }
            }
        } else {
            // Modo edición: actualizar
            $sql = "UPDATE productos SET 
                        nombre = '$nombre',
                        descripcion = '$descripcion',
                        cant_disponible = $cant
                    WHERE id = $id";
            if (!$conexion->query($sql)) {
                $error = "Error al actualizar: " . $conexion->error;
            } else {
                header("Location: ProductosEdit.php");
                exit();
            }
        }
    }
}

$resultado = $conexion->query("SELECT * FROM productos");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Productos</title>
    <style>
        body {
            font-family: Arial;
        }

        form,
        table {
            width: 80%;
            margin: auto;
            margin-top: 20px;
        }

        label {
            display: block;
            margin-top: 10px;
        }

        input[type=text],
        input[type=number],
        textarea {
            width: 100%;
            padding: 5px;
            margin-top: 5px;
        }

        table {
            border-collapse: collapse;
            margin-top: 40px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #eee;
        }

        .btn-editar {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
        }

        .btn-eliminar {
            background-color: #f44336;
            color: white;
            padding: 5px 10px;
        }

        .error {
            color: red;
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <h2 style="text-align:center;"><?= $modo_edicion ? 'Editar Producto' : 'Agregar Producto' ?></h2>

    <?php if ($error) : ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>ID:</label>
        <input type="number" name="id" value="<?= htmlspecialchars($id) ?>" <?= $modo_edicion ? 'readonly' : '' ?> required>

        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>

        <label>Descripción:</label>
        <textarea name="descripcion" required><?= htmlspecialchars($descripcion) ?></textarea>

        <label>Cantidad Disponible:</label>
        <input type="number" name="cant_disponible" value="<?= htmlspecialchars($cant) ?>" required>

        <br><br>
        <input type="submit" name="guardar" value="<?= $modo_edicion ? 'Actualizar' : 'Guardar' ?>">
    </form>

    <h2 style="text-align:center;">Lista de Productos</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Cantidad Disponible</th>
            <th>Acciones</th>
        </tr>

        <?php while ($row = $resultado->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['descripcion'])) ?></td>
                <td><?= $row['cant_disponible'] ?></td>
                <td>
                    <a href="?editar=<?= $row['id'] ?>" class="btn-editar">Editar</a>
                    <a href="?eliminar=<?= $row['id'] ?>" class="btn-eliminar" onclick="return confirm('¿Seguro que deseas eliminar este producto?');">Eliminar</a>
                </td>
            </tr>
        <?php } ?>
    </table>
    <a href=logout.php><button type="button" class="btn btn-success"> Cerrar Sesion</button></a>

</body>

</html>
