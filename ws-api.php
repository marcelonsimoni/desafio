<?php
ini_set('display_errors',1);
ini_set('display_startup_erros',1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
/*function __output_header__( $__success = true, $__message = null, $_dados = array() )
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        array(
            'success' => $__success,
            'message' => $__message,
            'dados'   => $_dados
        )
    );
    # por ser a ultima funcao, podemos matar o processo aqui.
    exit;
}
*/
class wsApi
{

    private $arqJson = 'tickets-modificado.json';

    /**
     * wsApi constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param $data
     * @return bool
     */
    public function validarData($data)
    {

        $data = str_ireplace('/', '-', $data);
        $data = explode('-', $data);
        if (checkdate($data[1], $data[2], $data[0]) == true) {
            return true;
        } else
            return false;

    }

    public function filtraTickets($array = array(
        'filtro' => 'dataCriacao',
        'ordenacao' => 'dataCriacao',
        'inicio' => '2017-12-20',
        'termino' => '2020-12-31',
        'tipoPrioridade' => 'ALTA',
        'paginacao' => 30,
        'pagina' => 1,
        ))
    {
        //print_r($array);

        /*
         * Padronizar os parametros
         */
        $array['tipoPrioridade'] = strtoupper($array['tipoPrioridade']);
        $array['filtro'] = strtoupper($array['filtro']);
        $array['ordenacao'] = strtoupper($array['ordenacao']);

        //print_r($array);
        $arquivo = json_decode(file_get_contents($this->arqJson), true);

        /*
         * Verifico se a data informada é valida e se o tipo de filtro é o correto para utilizar as datas
         */
        if (($this->validarData($array['inicio']) == false || $this->validarData($array['termino']) == false) && $array['filtro'] == 'DATACRIACAO') {

            return array(
                'erro' => true,
                'mensagem' => 'Por favor, informe datas válidas no formato YYY-mm-dd para início e término do período.',
                'retorno' => null,
            );

        } else {
            $inicio = strtotime($array['inicio'] . ' 00:00:00');
            $fim = strtotime($array['termino'] . " 23:59:59");
        }

        $arrayNumeroTicket = array();
        /*
         * Percorre o json para filtrar os tickets de acordo com o que foi pedido. Salva o ID do ticket em outro array
         * para depois fazer a ordenacao correta
         */
        foreach ($arquivo as $key => $ticket) {

            switch ($array['filtro']) {
                case 'DATACRIACAO':
                {
                    /*
                     * Verifica se a data de criacao está entre as datas de inicio e fim - range definido pelo usuario
                     */
                    $dataCriacao = strtotime($ticket['DateCreate']);
                    if ($dataCriacao >= $inicio && $dataCriacao <= $fim) {
                        array_push($arrayNumeroTicket, $key);
                    }

                    break;
                }
                default:
                {
                    /*
                     * confere se o tipo de prioridade é realmente alta ou normal
                     * Se sim, entra na condição e filtra pelo que solicitado, senão, guarda todos os tickets
                     */
                    if($array['tipoPrioridade'] == 'ALTA' || $array['tipoPrioridade'] == 'NORMAL') {
                        if (strtoupper($array['tipoPrioridade']) == $ticket['prioridade']) {
                            array_push($arrayNumeroTicket, $key);
                        }
                    } else {
                        array_push($arrayNumeroTicket, $key);
                    }

                    break;
                }
            }
        }

        /*
         * Arrays utilizados para a ordanacao
         */
        $arrayOrdenacao = array();
        $arrayOrdenacaoNormal = array();
        $arrayOrdenacaoAlta = array();

        /*
         * Percorre o array com os ID's dos tickets selecionados pela condição acima
         */

        for ($i = 0; $i < count($arrayNumeroTicket); $i++) {
            $ordenacaoData = $array['ordenacao'] == 'DATACRIACAO' ? 'DateCreate' : 'DateUpdate';
            switch ($array['filtro']) {
                case 'DATACRIACAO':
                {
                    $arrayOrdenacao[strtotime($arquivo[$arrayNumeroTicket[$i]][$ordenacaoData])] = $arquivo[$arrayNumeroTicket[$i]];
                    break;
                }
                default:
                {
                    if(strtoupper($arquivo[$arrayNumeroTicket[$i]]['prioridade']) == 'ALTA') {
                        $arrayOrdenacaoAlta[strtotime($arquivo[$arrayNumeroTicket[$i]][$ordenacaoData])] = $arquivo[$arrayNumeroTicket[$i]];
                    } else {
                        $arrayOrdenacaoNormal[strtotime($arquivo[$arrayNumeroTicket[$i]][$ordenacaoData])] = $arquivo[$arrayNumeroTicket[$i]];
                    }

                    break;
                }
            }
        }

        /*
         * Ordena os arrays por ordenacao - já que por prioridade já será aplicado no filtro
         */
        if($array['filtro'] == 'PRIORIDADE') {
            ksort($arrayOrdenacaoAlta);
            ksort($arrayOrdenacaoNormal);
            $arrayOrdenacao = array_merge($arrayOrdenacaoAlta, $arrayOrdenacaoNormal);
        } elseif ($array['filtro'] == 'DATACRIACAO') {
            ksort($arrayOrdenacao);
        }

        if(count($arrayOrdenacao) > 0) {

            $totalPaginas = 1;
            /*
             * Cria a paginacao de acordo com os parametros estabelecidos
             */
            if(count($arrayOrdenacao) > $array['paginacao']) {
                $totalPaginas = ceil(count($arrayOrdenacao) / $array['paginacao']);

                $inicio = ($array['pagina'] - 1) * ($array['paginacao']) ;
                if($inicio < 0) $inicio = 0;

                $arrayOrdenacao = array_slice($arrayOrdenacao, $inicio, $array['paginacao']);
            }

            /*
             * Retorno em json
             */
            return array(
                'erro' => 0,
                'mensagem' => 'Encontrados ' . count($arrayOrdenacao) . ' tickets com os parâmetros informados.',
                'totalPaginas' => $totalPaginas,
                'tickets' => $arrayOrdenacao,
            );
        } else {
            return array(
                'erro' => 1,
                'mensagem' => 'Nenhum ticket encontrado com os parâmetros de busca.',
                'totalPaginas' => 0,
                'tickets' => null,
            );
        }

    }
}

$array = array(
    'filtro' => 'datacriacao',
    'ordenacao' => 'datacriacao',
    'inicio' => '2017-12-01',
    'termino' => '2020-12-31',
    'tipoPrioridade' => 'alta',
    'paginacao' => 30,
    'pagina' => 1,
);

if(!empty($_REQUEST['filtro'])) {
    $array['filtro'] = $_REQUEST['filtro'];
}

if(!empty($_REQUEST['ordenacao'])) {
    $array['ordenacao'] = $_REQUEST['ordenacao'];
}

if(!empty($_REQUEST['inicio'])) {
    $array['inicio'] = $_REQUEST['inicio'];
}

if(!empty($_REQUEST['termino'])) {
    $array['termino'] = $_REQUEST['termino'];
}

if(!empty($_REQUEST['tipoPrioridade'])) {
    $array['tipoPrioridade'] = $_REQUEST['tipoPrioridade'];
}

if(!empty($_REQUEST['paginacao'])) {
    $array['paginacao'] = (int)$_REQUEST['paginacao'] > 0 ? (int)$_REQUEST['paginacao'] : 3;
}

if(!empty($_REQUEST['pagina'])) {
    $array['pagina'] = (int)$_REQUEST['pagina'] == 0 ? 1 : (int)$_REQUEST['pagina'];
}

$classAPI = new wsApi();

$retorno = $classAPI->filtraTickets($array);

if(!empty($_REQUEST['impressao'])) {
    if(strtoupper($_REQUEST['impressao']) == 'CASCATA') {
        print_r($retorno);
    } else {
        echo json_encode($retorno);
    }

} else {
    echo json_encode($retorno);
}
/*
PARAMETROS PARA A URL
filtro = dataCriacao ou prioridade
ordenacao = dataCriacao, dataAtualizacao ou prioridade
inicio = data no formato: dd/mm/YYYY
termino = data no formato: dd/mm/YYYY
tipoPrioridade = alta ou normal (só será utilizada caso o filtro seja por prioridade)
paginacao = define o número de resultados por página (por default está marcado com 3 tickets/pagina)
pagina = página do resultado que será exibida como resultado
impressao = linha ou cascata





Um algoritmo que classifique nossos tickets
Uma API que exponha nossos tickets com os seguintes recursos
Ordenação por: Data Criação, Data Atualização e Prioridade
Filtro por: Data Criação (intervalo) e Prioridade
Paginação

sudo chcon -t httpd_sys_rw_content_t /var/www/html/desafio -R

 */