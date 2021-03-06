<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Thiago Monteiro
 * Date: 11/08/11
 * Time: 11:08
 * To change this template use File | Settings | File Templates.
 */
 
class ConviteDAO extends WeLearn_DAO_AbstractDAO
{
    protected $_nomeCF = 'convites_convite_basico';

    private $_usuarioDao;
     /**
     * @param mixed $id
     * @return WeLearn_DTO_IDTO
     */


    public function __construct()
    {
        $this->_usuarioDao = WeLearn_DAO_DAOFactory::create('UsuarioDAO');
    }



    public function recuperar($id)
    {
        // TODO: Implementar este metodo
    }

    /**
     * @param mixed $de
     * @param mixed $ate
     * @param array|null $filtros
     * @return array
     */
    public function recuperarTodos($de = null, $ate = null, array $filtros = null)
    {
        $resultado= $this->_cf->multiget($filtros['convites'],null,$de,$ate,true,$filtros['count']);
        $cassandra= $this->_criarVariosFromCassandra($resultado);
        return $cassandra;
    }

    /**
     * @param mixed $de
     * @param mixed $ate
     * @return int
     */
    public function recuperarQtdTotal($de = null, $ate = null)
    {
        // TODO: Implementar este metodo
    }

    /**
     * @param mixed $id
     * @return WeLearn_DTO_IDTO
     */
    public function remover($id)
    {
        // TODO: Implementar este metodo
    }

    /**
     * @param array|null $dados
     * @return WeLearn_DTO_IDTO
     */
    public function criarNovo(array $dados = null)
    {
        return new WeLearn_Convites_ConviteBasico($dados);
    }

    /**
     * @param WeLearn_DTO_IDTO $dto
     * @return boolean
     */
    protected function _atualizar(WeLearn_DTO_IDTO $dto)
    {
        // TODO: Implementar este metodo
    }

    /**
     * @param WeLearn_DTO_IDTO $dto
     * @return boolean
     */
    protected function _adicionar(WeLearn_DTO_IDTO &$dto)
    {
        $UUID = UUID::mint();
        $dto->setId($UUID->string);
        $this->_cf->insert($UUID->bytes, $dto->getConviteBasicoToCassandra());

    }

    /**
     * @param ArrayofUsuarios $usuarios
     * @param array $dadosConvite
     * @return void
     */
    public function enviarCadastradoCurso(ArrayofUsuarios $usuarios, array $dadosConvite)
    {
        // TODO: Implementar este metodo
    }

    /**
     * @param array $usuarios
     * @param WeLearn_Cursos_Curso $Curso
     * @return void
     */
    public function retirarUsuariosVinculadosAoCurso(Array $usuarios, WeLearn_Cursos_Curso $Curso)
    {
        // TODO: Implementar este metodo
    }


    /**
     * @param array $visitantes
     * @param array $dadosConvite
     * @return void
     */
    public function enviarVisitanteCurso(Array $visitantes, Array $dadosConvite)
    {
        // TODO: Implementar este metodo
    }

    /**
     * @param array $visitantes
     * @return void
     */
    public function retirarUsuariosAtivosNoServico(Array $visitantes)
    {
        // TODO: Implementar este metodo
    }

    private function _criarFromCassandra(array $column,
                                         WeLearn_Usuarios_Usuario $remetentePadrao = null
                                         )
    {
        if ($remetentePadrao instanceof WeLearn_Usuarios_Usuario) {
            $column['remetente'] = $remetentePadrao;
        } else {
            $column['remetente'] = $this->_usuarioDao->recuperar($column['remetente']);
        }



        $convite = $this->criarNovo();
        $convite->fromCassandra($column);

        return $convite;
    }

    private function _criarVariosFromCassandra(array $columns,
                                               WeLearn_Usuarios_Usuario $remetentePadrao = null
                                               )
    {
        $arrayConvites = array();

        foreach ( $columns as $column ) {
            $arrayConvites[] = $this->_criarFromCassandra($column, $remetentePadrao);
        }

        return $arrayConvites;
    }


}
