<?php
/**
 *  This file is part of SNEP.
 *  Para território Brasileiro leia LICENCA_BR.txt
 *  All other countries read the following disclaimer
 *
 *  SNEP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  SNEP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with SNEP.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Classe para coleta de informações sobre o asterisk.
 * Essa classe tem a intencaoo de ser uma classe de compatibilidade para o
 * snep ou seja, ainda precisa ser reescrito algum codigo de outras classes
 * para uma melhor utilizacao do código, isso inclui a classe
 * AGI_AsteriskManager que precisa ser adaptada para Singleton
 */
class AsteriskInfo {
    /**
     * Objeto para coleta de informações via AMI. (Singleton)
     * @var AGI_AsteriskManager $asterisk
     */
    private static $asterisk;

    /**
     * Construtor do objeto
     */
    public function AsteriskInfo() {
        global $SETUP;
        // Verificando se já existe alguma conex�o
        if(!isset(self::$asterisk)) {

            // Criando a primeira instancia da conexão
            try {
                self::$asterisk = PBX_Asterisk_AMI::getInstance();
                $res = false ;
            } catch (Exception $e) {
                $res = $e ;
            }
            if ($res) {
                throw new Asterisk_Exception_CantConnect("Unable to connect to manager");
            }

        }
    }

    /**
     * Método para compactibilidade com código do snep, aqui há uma melhoria
     * nas consultas simples reutilizando socket onde é possível.
     *
     * @param string $comando - Comando a ser executado
     *
     * @param string $quebra - Para retornar somente as linhas que contenham
     * o conteudo dessa variável
     *
     * @param boolean $tudo - Esse parametro não é usado (?!)
     *
     * @return Dados da consulta
     */
    public function status_asterisk($comando, $quebra,  $tudo=False) {
        if($comando != "Agents" && $comando != "Status") {
            $cmd = self::$asterisk->command($comando);

            $retorno = $cmd['data'];
            if ($quebra != "") {
                $ret_quebrado = " ";

                foreach(explode("\n", $cmd['data']) as $line) {
                    if (preg_match("/$quebra/", $line)) {
                        $ret_quebrado .= $line;
                        break;
                    }
                }
                return $ret_quebrado;
            }
            else {
                return $cmd['data'];
            }
        } else {
            // Enviando requisição de status
            self::$asterisk->send_request($comando, array());
            // Enviando esse objeto para cuidar dos responses
            self::$asterisk->wait_event($this);

            return $this->return;
        }
    }

    /**
     * @var String que guardara o retorno do que for processado no metodo
     * processEvent()
     */
    private $return;

    /**
     * Metodo que faz o tratamento dos eventos Agent, não deveria existir
     * já que essas informações deveriam vir em respostas e não em events.
     * @param $event evento que está sendo processado
     * @param $param parametros que vieram com o evento
     * @return boolean - true se o processamento de eventos deve parar.
     */
    function processEvent($event, $param) {
        if($event == "agents" || $event == "status") {
            foreach($param as $key => $value) {
                $this->return .= "$key: $value\r\n";
            }
            $this->return .= "\r\n\r\n";
        }
        else if($event == "agentscomplete" || $event == "statuscomplete")
            return true;
    }
}
