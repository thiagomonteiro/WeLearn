<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 16/05/12
 * Time: 17:37
 * To change this template use File | Settings | File Templates.
 */
class Feed extends Home_Controller
{

    function __construct()
    {
        parent::__construct();
    }
    public function index()
    {

    }

    public function criarFeed()
    {

        set_json_header();
        $tipo=$this->input->post('tipo-feed');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('conteudo-feed', 'conteudo-feed', 'required');
        if($tipo != WeLearn_Compartilhamento_TipoFeed::STATUS)
        {
            $this->form_validation->set_rules('descricao-feed', 'descricao-feed', 'callback_validar_descricao');
        }

        if($this->form_validation->run()===false)
        {
            $json = create_json_feedback(false, validation_errors_json());
            exit($json);
        }


        $feedDao = WeLearn_DAO_DAOFactory::create('FeedDAO');
        $feedUsuario = $feedDao->criarNovo();
        $criador=$this->autenticacao->getUsuarioAutenticado();
        $conteudo=$this->input->post('conteudo-feed');


        if($tipo != WeLearn_Compartilhamento_TipoFeed::STATUS)
        {
            $descricao=$this->input->post('descricao-feed');
            $feedUsuario->setDescricao($descricao);
        }


        $feedUsuario->setConteudo($conteudo);
        $feedUsuario->setTipo($tipo);
        $feedUsuario->setCriador($criador);
        $feedUsuario->setDataEnvio(time());
        $this->load->helper('notificacao_js');
        try{

            $feedDao->salvar($feedUsuario);
            $notificacoesFlash = create_notificacao_json(
                'sucesso',
                'Feed enviado com sucesso!'
            );
            $this->session->set_flashdata('notificacoesFlash', $notificacoesFlash);
            $json = create_json_feedback(true);
        }catch(cassandra_NotFoundException $e){
            $json=create_json_feedback(false);
        }

        echo $json;
    }


    public function criarTimeLine($idPerfil)
    {
        set_json_header();
        $tipo=$this->input->post('tipo-feed');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('conteudo-feed', 'conteudo-feed', 'required');
        if($tipo != WeLearn_Compartilhamento_TipoFeed::STATUS)
        {
            $this->form_validation->set_rules('descricao-feed', 'descricao-feed', 'callback_validar_descricao');
        }

        if($this->form_validation->run()===false)
        {
            $json = create_json_feedback(false, validation_errors_json());
            exit($json);
        }

        $usuarioPerfil= WeLearn_DAO_DAOFactory::create('UsuarioDAO')->recuperar($idPerfil);// usuario do perfil

        $feedDao = WeLearn_DAO_DAOFactory::create('FeedDAO');
        $feedUsuario = $feedDao->criarNovo();
        $criador=$this->autenticacao->getUsuarioAutenticado();
        $conteudo=$this->input->post('conteudo-feed');


        if($tipo != WeLearn_Compartilhamento_TipoFeed::STATUS)
        {
            $descricao=$this->input->post('descricao-feed');
            $feedUsuario->setDescricao($descricao);
        }


        $feedUsuario->setConteudo($conteudo);
        $feedUsuario->setTipo($tipo);
        $feedUsuario->setCriador($criador);
        $feedUsuario->setDataEnvio(time());
        $this->load->helper('notificacao_js');
        try{

            $feedDao->salvarTimeLine($feedUsuario,$usuarioPerfil);
            $notificacoesFlash = create_notificacao_json(
                'sucesso',
                'Feed enviado com sucesso!'
            );
            $this->session->set_flashdata('notificacoesFlash', $notificacoesFlash);
            $json = create_json_feedback(true);
        }catch(cassandra_NotFoundException $e){
            $json=create_json_feedback(false);
        }

        echo $json;
    }

    public function validar_descricao($str)
    {
        if (is_null($str))
        {
            $this->form_validation->set_message('validar_descricao', 'The %s field is required');
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }


    public function validar_url()
    {
        $url=$this->input->post('conteudo-feed');
        $this->load->library('autoembed');
        $isValid = $this->autoembed->parseUrl($url);

        if($isValid)
        {
            $json = create_json_feedback(true);
        }
        else{
            log_message(
                'error',
                'A url do video enviado nao é valida'
            );

            $error = create_json_feedback_error_json(
                'A url do video enviado não é valida, verifique se a url está correta.'
            );

            $json = create_json_feedback(false, $error);
        }


        echo $json;


    }

}