<?php
/**
 * Created by JetBrains PhpStorm.
 * User: thiago
 * Date: 28/03/12
 * Time: 19:13
 * To change this template use File | Settings | File Templates.
 */
class Mensagem extends WL_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->template->setTemplate('home')
                       ->appendJSImport('home.js')
                       ->appendJSImport('mensagem.js');
    }

    public function index()
    {
        $mensagemDao = WeLearn_DAO_DAOFactory::create('MensagemPessoalDAO');

        try {
            $dados = $mensagemDao->recuperarListaAmigosMensagens(
                $this->autenticacao->getUsuarioAutenticado()
            );

            $dadosView = array(
                'mensagens' => $dados,
                'success'=> true
            );

        } catch (Exception $e) {
            $dadosView=array( 'success' => false );
        }

        $this->_renderTemplateHome('usuario/mensagem/index', $dadosView);
    }

    public function listar($idAmigo='')
    {
        try{

            if( $idAmigo=='' ) {
                redirect($this->index());
            }else{

            $count=10;

            $usuario = $this->autenticacao->getUsuarioAutenticado();
            $amigo = WeLearn_DAO_DAOFactory::create('UsuarioDAO')->recuperar($idAmigo);
            $mensagemDao = WeLearn_DAO_DAOFactory::create('MensagemPessoalDAO');


            try {
                $listaMensagens = $mensagemDao->recuperarTodosPorUsuario($usuario,
                                                                         $amigo,
                                                                         '', '',
                                                                         $count + 1);
            } catch (cassandra_NotFoundException $e) {
                $listaMensagens = array();
            }

            $this->load->helper('paginacao_cassandra');
            $dadosPaginados = create_paginacao_cassandra($listaMensagens, $count);
            $dadosPaginados= array_reverse($dadosPaginados);

            $partialListaMensagens = $this->template->loadPartial(
                'lista',
                array(
                    'mensagens' => $listaMensagens,
                    'paginacao' => $dadosPaginados,
                    'idAmigo' => $amigo->getId(),
                    'nomeAmigo'=>$amigo->getNome(),
                    'inicioProxPagina' => $dadosPaginados['inicio_proxima_pagina'],
                    'haMensagens' => $dadosPaginados['proxima_pagina']
                ),
                'usuario/mensagem'
            );

            $partialEnviarMensagem = $this->template->loadPartial(
                'criar',
                array( 'idDestinatario' => $idAmigo ),
                'usuario/mensagem'
            );

            $dadosView = array(
                'listaMensagens' => $partialListaMensagens,
                'enviarMensagem' => $partialEnviarMensagem
            );

            $this->_renderTemplateHome('usuario/mensagem/listar', $dadosView);
        }
        }catch(Exception $e) {
            log_message('error', 'Erro ao tentar exibir lista de Enquetes: '
                . create_exception_description($e));

            show_404();
        }

    }


    public function proxima_pagina($idAmigo, $inicio)
    {
        if ( ! $this->input->is_ajax_request() ) {
            show_404();
        }

        try{
            $count = 10;

            $usuario = $this->autenticacao->getUsuarioAutenticado();
            $destinatario = WeLearn_DAO_DAOFactory::create('UsuarioDAO')->recuperar($idAmigo);
            $mensagemDao = WeLearn_DAO_DAOFactory::create('MensagemPessoalDAO');

            try {
                $listaMensagens = $mensagemDao->recuperarTodosPorUsuario($usuario,
                                                                         $destinatario,
                                                                         $inicio,
                                                                         '',
                                                                         $count + 1);
            } catch(cassandra_NotFoundException $e) {
                $listaMensagens= array();
            }

            $this->load->helper('paginacao_cassandra');
            $dadosPaginados = create_paginacao_cassandra($listaMensagens, $count);
            $dadosPaginados= array_reverse($dadosPaginados);

            $response = array(
                'success' => true,
                'htmlListaMensagens' => $this->template->loadPartial(
                    'lista',
                    array(
                        'idAmigo' => $idAmigo,
                        'haMensagens'=> !empty($dadosPaginados),
                        'mensagens' => $listaMensagens,
                        'inicioProxPagina' => $dadosPaginados['inicio_proxima_pagina']
                    ),
                    'usuario/mensagem'
                ),
                'paginacao' => $dadosPaginados
            );

            $json = Zend_Json::encode($response);

        } catch (UUIDException $e) {

            log_message(
                'error',
                'Ocorreu um erro ao tentar recupera uma nova página de enquetes: '
                    . create_exception_description($e)
            );

            $error = create_json_feedback_error_json(
                'Ocorreu um erro inesperado, já estamos verificando.
                Tente novamente mais tarde.'
            );

            $json = create_json_feedback(false, $error);

        }

        echo $json;
    }

    public function criar()
    {
        if ( ! $this->input->is_ajax_request() ) {
            show_404();
        }

        set_json_header();

        $this->load->library('form_validation');

        if ($this->form_validation->run() === FALSE) {

            $json = create_json_feedback(false, validation_errors_json());

            exit($json);
        }

        $mensagem = $this->input->post('mensagem');
        $idDestinatario = $this->input->post('destinatario');

        $remetente = $this->autenticacao->getUsuarioAutenticado();
        $destinatario = WeLearn_DAO_DAOFactory::create('UsuarioDAO')->recuperar($idDestinatario);

        $mensagemDao = WeLearn_DAO_DAOFactory::create('MensagemPessoalDAO');

        $mensagemObj = $mensagemDao->criarNovo();
        $mensagemObj->setMensagem($mensagem);
        $mensagemObj->setDestinatario($destinatario);
        $mensagemObj->setRemetente($remetente);
        $mensagemDao->salvar($mensagemObj);


        $response = array(
            'success' => true,
             'mensagemId'=>$mensagemObj->getId(),
             'remetenteId'=>$mensagemObj->getRemetente()->getId(),
             'mensagemTexto'=>$mensagemObj->getMensagem(),
             'dataEnvio'=>$mensagemObj->getDataEnvio()
        );

        $json = Zend_Json::encode($response);

        echo $json;
    }


    public function remover($idMensagem,$idAmigo)
    {
        if ( ! $this->input->is_ajax_request() ) {
            show_404();
        }

        set_json_header();

        try{
            $usuario = $this->autenticacao->getUsuarioAutenticado();
            $amigo = WeLearn_DAO_DAOFactory::create('UsuarioDAO')->recuperar($idAmigo);;

            $mensagemPessoalDao =  WeLearn_DAO_DAOFactory::create('MensagemPessoalDAO');

            $mensagemPessoal = $mensagemPessoalDao->criarNovo();
            $mensagemPessoal->setDestinatario($amigo);
            $mensagemPessoal->setRemetente($usuario);

            $mensagemPessoalDao->remover($idMensagem);

            $json=Zend_Json::encode(array( 'success' => true ));

        } catch( Exception $e ) {

           $json = create_json_feedback();
        }

        echo $json;
    }

    private function _renderTemplateHome($view = '', $dados = array())
    {
        $dadosBarraEsquerda = array(
            'usuario' => $this->autenticacao->getUsuarioAutenticado()
        );

        $dadosBarraDireita = array(

        );

        $this->template->setDefaultPartialVar('home/barra_lateral_esquerda', $dadosBarraEsquerda)
            ->setDefaultPartialVar('home/barra_lateral_direita', $dadosBarraDireita)
            ->render($view, $dados);
    }
}