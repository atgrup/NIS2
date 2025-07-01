<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=
    , initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="./assets/styles/style.css">
    <!--google fonts-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap"
        rel="stylesheet">

</head>
<style>
    .indexStencil {
        width: 290px;
        height: 895px;
        background: #072989;
    }

    .bg-mi-color {
        background-color: #072989;
        color: white;
        border: 20px;
    }

    .stencil {
        display: flex;
        flex-direction: row;
    }

    .btns {
        gap: 55%;
        display: flex;
    }

    .contenedorTablaStencil {
        border-radius: 40px;
        background: #FFF;
        padding: 30px;
        width: 73%;
        height: 850px;
        display: flex;
        align-self: center;
        flex-direction: column;
        margin-left: 20px;
    }

    .stencilBody {
        background-color: #E6E6E6;
    }

    .cajaArchivos {
        display: flex;
        flex-direction: row;
        color: white;
        justify-content: center;
        gap: 50px;
    }

    .footerNaV {
        color: #FFF;
        font-family: "Instrument Sans";
        font-size: 15px;
        font-weight: 400;
        text-align: center;
        position: absolute;
        bottom: 40px;
        left: 4%;
    }

    .tituloNIS {
        color: #FFF;
        font-family: "Instrument Sans";
        font-size: 36px;
        font-style: normal;
        font-weight: 700;
        line-height: normal;
        margin-top: 70px;
        text-align: center;
    }

    .menuNav {
        margin-top: 70px;
        gap: 50px;
        display: flex;
        flex-direction: column;
    }
    .headertable{
        margin-top:100px;
    }
    .imgEmpresa{
        position: absolute;
        bottom: 50px;
        border-radius: 120px;
        width: 60px;
        height: auto;
    }
</style>

<body class="stencilBody">
    <main class="stencil">
        <nav class="indexStencil">
            <h1 class="tituloNIS">NIS2</h1>
            <div class="menuNav">
                <div class="cajaArchivos">
                    <div class="textoStencil">Gernador 1</div>
                    <div class="textoStencil"> imagen</div>
                </div>
                <div class="cajaArchivos">
                    <div class="textoStencil">Gernador 1</div>
                    <div class="textoStencil"> imagen</div>


                </div>
                <div class="cajaArchivos">
                    <div class="textoStencil">Gernador 1</div>
                    <div class="textoStencil"> imagen</div>
                </div>
            </div>
            <div class="footerNaV">
                Política de cookies<br>
                Terminos y condiciones
            </div>
        </nav>

        <div class="contenedorTablaStencil">
            <div class="btns">
                <button type="button" class="btn bg-mi-color  btn-lg">
                    <img src="img/Arrow 1.png">
                </button>
                <div class="col-sm">
                    <button type="button" class="btn bg-mi-color  btn-lg">
                        Normativas
                    </button>
                    <button type="button" class="btn bg-mi-color  btn-lg">
                        Criterios de la NIS2
                    </button>
                </div>
            </div>
            <div class="headertable">
                <table class="table table-bordered border-secondary">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">First</th>
                            <th scope="col">Last</th>
                            <th scope="col">Handle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">1</th>
                            <td>Mark</td>
                            <td>Otto</td>
                            <td>@mdo</td>
                        </tr>
                        <tr>
                            <th scope="row">2</th>
                            <td>Jacob</td>
                            <td>Thornton</td>
                            <td>@fat</td>
                        </tr>
                        <tr>
                            <th scope="row">3</th>
                            <td>John</td>
                            <td>Doe</td>
                            <td>@social</td>
                        </tr>
                    </tbody>
                </table>
                <img src="img/banderita.png" class="imgEmpresa">
            </div>
        </div>

    </main>
</body>

</html>