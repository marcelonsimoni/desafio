<?php
if(file_exists('tickets-modificado.json'))
    unlink('tickets-modificado.json');

ini_set('display_errors',1);
ini_set('display_startup_erros',1);
error_reporting(E_ALL);

$arquivo = json_decode(file_get_contents('tickets.json'),true);

/*
 * Palavras chaves que remetem a insatisfação
 */
$arrayInsatisfacao = ['Reclamação','Diferente','troca','troco','cancelamento','procon','reclame','insatisfeito','defeito','produto', 'ReclameAqui'];

/*
 * Palavras chaves que remetem a satisfação
 */
$arraySatisfacao = ['Elogios','Parabéns','Sugestões'];

/*
 * Limite de HORAS para determinar se o ticket está demorando para ser atendido
 */
$limiteHoras = 24;

/*
 * Declaração de alguns vetores e variáveis a serem utilizadas
 */
$arrayClientes = array();
$nivelAssertividade = 70;
$arrayFinalJson = array();
$orgaoDeProtecao = false;

foreach ($arquivo as $key => $ticket) {

    $dataAbertura = strtotime($ticket['DateCreate']);
    $dataUltimaInteracao = strtotime($ticket['DateUpdate']);
    $primeiraVezCliente = true;

    /*
     * Verifica se o cliente já fez um chamado anteriormente. Caso tenha feito, o próximo chamado entrará com
     * prioridade ALTA
     */
    if (array_search($ticket['CustomerID'], $arrayClientes) != null) {
        $primeiraVezCliente = false;
    }
    $validado = false;
    $percentoInsatisfacao = 0;
    $percentoSatisfacao = 0;
    $percentoNORMAL = 0;
    $rodada = 0;
    $ultimaInteracao = 'Expert';
    /*
     * Guarda a posicao da penultima interacao feita no chamado
     */
    $totalInteracoes = count($ticket['Interactions']) - 2;
    foreach ($ticket['Interactions'] as $key2 => $interacao) {

        if ($totalInteracoes == $key2) {
            $dataAbertura = strtotime($interacao['DateCreate']);
        }
        if ($rodada == 0) {
            /*
             * Verifica o nível (%) de insatisfacao
             */
            foreach ($arrayInsatisfacao as $insatisfacao) {

                similar_text($interacao['Subject'], $insatisfacao, $tmp);
                if ($tmp > $percentoInsatisfacao) $percentoInsatisfacao += $tmp;

                /*
                 * Verifica se a reclamação é sobre o procon ou reclameAqui
                 *  procura no subject e na mensagem se existem essas palavras
                 */
                if (stripos($interacao['Subject'], 'procon') !== false
                    || stripos($interacao['Subject'], 'ReclameAqui') !== false
                    || stripos($interacao['Message'], 'procon') !== false
                    || stripos($interacao['Message'], 'ReclameAqui') !== false
                ) {
                    $orgaoDeProtecao = true;
                }

            }

            /*
             * Verifica o nível (%) de satisfacao
             */
            foreach ($arraySatisfacao as $satisfacao) {
                similar_text($interacao['Subject'], $satisfacao, $tmp);
                if ($tmp > $percentoSatisfacao) $percentoSatisfacao += $tmp;
            }
            $rodada++;
        }
        /*
         * Salva quem fez a ultima interação do chamado
         */
        $ultimaInteracao = $interacao['Sender'];

    }

    /*
     * Calcula a diferença de horas da penultima interacao para a ultima
     */
    $diferencaHoras = $diferencaHorasOriginal = ($dataUltimaInteracao - $dataAbertura) / (60 * 60);

    /*
     * Se a ultima interação foi o atendente, o chamado fica aguardando o retorno do cliente e a diferença de
     * horas fica zerada
     */
    if ($ultimaInteracao == 'Expert') {
        $diferencaHoras = 0;
    }

    /*
     * Se o nível de insatisfação for maior que 70 ou, a diferenca em horas, for maior que a estipulada no
     * parâmetro com a última interação feita pelo cliente (que aguarda atendimento) = ALTA
     */
    if ($percentoInsatisfacao >= $nivelAssertividade || ($percentoSatisfacao < $nivelAssertividade && $diferencaHoras > $limiteHoras && $ultimaInteracao == 'Customer')) {

        /*
         *
         */
        if ($diferencaHoras > $limiteHoras && $ultimaInteracao == 'Customer') {
            $motivo = 2;
        } elseif ($orgaoDeProtecao == true) {
            $motivo = 3;
        } else {
            $motivo = 1;
        }

        /*
         * Apenas verifica se o codigo já foi inserido no array, se sim, não insere novamente.
         */
        if ($primeiraVezCliente == true) array_push($arrayClientes, $ticket['CustomerID']);
        //echo $ultimaInteracao . ' - ALTA: ' . $interacao['Subject'] . ' -> ' . $diferencaHoras . ' dias ';
        $ticket['similaridade'] = $percentoInsatisfacao;
        $ticket['prioridade'] = 'ALTA';
        $ticket['motivo'] = $motivo;
        /*
         * Se a satisfação for maior que 70% ou a ultima interação for do atendimento, a prioridade é NORMAL
         */
    } elseif ($percentoSatisfacao >= $nivelAssertividade || $ultimaInteracao == 'Expert') {
        //echo $ultimaInteracao . ' - NORMAL: ' . $interacao['Subject'] . ' -> ' . $diferencaHoras . ' dias ';
        $ticket['similaridade'] = $percentoSatisfacao;
        $ticket['prioridade'] = 'NORMAL';
        $ticket['motivo'] = ($percentoSatisfacao >= $nivelAssertividade) ? 2 : 1;
        /*
         * Se a ultima interação for do cliente e espera mais horas do que o parametro, a prioridade é ALTA
         */
    } else {
        /*
         * Apenas verifica se o codigo já foi inserido no array, se sim, não insere novamente.
         */
        $ticket['similaridade'] = $percentoInsatisfacao;
        $ticket['motivo'] = 2;
        $ticket['prioridade'] = 'ALTA';

        if ($primeiraVezCliente == true) array_push($arrayClientes, $ticket['CustomerID']);
        //echo $ultimaInteracao . ' - ALTA11111: ' . $interacao['Subject'] . ' -> ' . $diferencaHoras . ' dias ';
    }
    $ticket['horasAberto'] = floor($diferencaHorasOriginal);
    array_push($arrayFinalJson, $ticket);
    //echo '<br>';
    //print_r($ticket);
}

file_put_contents('tickets-modificado.json', json_encode($arrayFinalJson));
