<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width= initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="./assets/styles/style.css">
    <!--google fonts-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap"
        rel="stylesheet">

</head>
<style>
    .homeIndex {
        border-radius: 10px;
        background: #072989;
        width: 1000px;
        height: 610px;
        flex-shrink: 0;
        margin-left: 70px;
        padding: 30px;
    }

    .titulo {
        color: #FFF;
        font-family: "Instrument Sans", sans-serif;
        font-size: 20px;
        font-style: normal;
        font-weight: 700;
        line-height: normal;
    }

    .introIndex {
        display: flex;
        flex-direction: column;
        align-items: left;
        flex-wrap: wrap;
        color: #FFF;
        font-family: "Instrument Sans";
        font-size: 16px;
        gap: 40px;
        font-style: normal;
        font-weight: 400;
        line-height: normal;
        width: 843px;
    }

    .tituloIndice {
        color: #5E5E5E;
        font-family: "Instrument Sans";
        font-size: 60px;
        font-style: normal;
        font-weight: 700;
        line-height: normal;

    }

    .indexNav {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 370px;

        margin-left: 70px;

    }

    .registerButn {

        color: #4D4D4D;
        text-align: center;
        font-family: "Instrument Sans";
        font-size: 18px;
        font-style: normal;
        padding: 2px 10px;
        font-weight: 700;
        line-height: normal;
        text-transform: uppercase;
    }

    .candadito {
        width: 200px;
        height: auto;
    }

    .btnsnav {
        display: flex;
        gap: 30px;
    }
    .funcionesIndex{
        border: 0.5px solid white;
border-radius: 20px;
padding: 20px;
    }
</style>

<body>
    <nav class="indexNav">
        <h1 class="tituloIndice">Bienvenido/a</h1>
        <div class="btnsnav">
            <a href="registro.php" class="registerButn">registrar</a>
            <a href="login.php" class="registerButn">iniciar sesión</a>
        </div>
    </nav>

    <main class="homeIndex">
        <section class="introIndex">
            <div class="titulo"> Nuevas restricciones de la NIS2</div>
            Las empresas afectadas deberán cumplir con estrictas obligaciones de gestión de riesgos, notificación de
            incidentes de seguridad en plazos cortos y adoptar medidas técnicas y organizativas adecuadas.
            Para los proveedores, esto implica mayores responsabilidades contractuales y técnicas, ya que las empresas
            deberán garantizar que toda su cadena de suministro cumple también con los requisitos de seguridad, lo que
            podría traducirse en auditorías, controles más estrictos y posibles sanciones en caso de incumplimiento.
            <img src="img/candadito.png" class="candadito">
            <div class="funcionesIndex">
                <div class="titulo">Consultores</div>
                Si eres consultor o auditor, ponte en contacto con tu empresa para darte de alta en esta plataforma.
                
            </div>
        </section>
    </main>
</body>

</html>