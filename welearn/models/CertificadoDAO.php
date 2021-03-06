<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Thiago Monteiro
 * Date: 11/08/11
 * Time: 10:52
 * To change this template use File | Settings | File Templates.
 */
 
class CertificadoDAO extends WeLearn_DAO_AbstractDAO
{
    const MAX_CERTIFICADOS = 20;

    protected $_nomeCF = 'cursos_certificado';

    private $_nomeCertificadosPorCursoCF = 'cursos_certificado_por_curso';
    private $_nomeCertificadosPorAlunoCF = 'cursos_certificado_por_aluno';

    /**
     * @var ColumnFamily
     */
    private $_certificadosPorCursoCF;

    /**
     * @var ColumnFamily
     */
    private $_certificadosPorAlunoCF;

    /**
     * @var CursoDAO
     */
    private $_cursoDao;

    function __construct()
    {
        $phpCassa = WL_Phpcassa::getInstance();

        $this->_certificadosPorCursoCF = $phpCassa->getColumnFamily(
            $this->_nomeCertificadosPorCursoCF
        );

        $this->_certificadosPorAlunoCF = $phpCassa->getColumnFamily(
            $this->_nomeCertificadosPorAlunoCF
        );

        $this->_cursoDao = WeLearn_DAO_DAOFactory::create('CursoDAO');
    }

    /**
     * @param mixed $id
     * @return WeLearn_DTO_IDTO
     */
    public function recuperar($id)
    {
        $UUID = UUID::import( $id );

        $column = $this->_cf->get( $UUID->bytes );

        return $this->_criarFromCassandra( $column );
    }

    /**
     * @param WeLearn_Cursos_ParticipacaoCurso $participacaoCurso
     */
    public function registrarFimDeCurso(WeLearn_Cursos_ParticipacaoCurso &$participacaoCurso)
    {
        $certificado = $this->recuperarAtivoPorCurso( $participacaoCurso->getCurso() );
        $participacaoCurso->setCertificado( $certificado );

        $this->_certificadosPorAlunoCF->insert(
            $participacaoCurso->getAluno()->getId(),
            array( UUID::import( $certificado->getId() )->bytes => '' )
        );
    }

    /**
     * @param WeLearn_Cursos_Curso $curso
     * @return WeLearn_DTO_IDTO
     */
    public function recuperarAtivoPorCurso(WeLearn_Cursos_Curso $curso)
    {
        $cursoUUID = UUID::import( $curso->getId() );

        $idCertificadoAtivo = $this->_cursoDao->getCf()->get(
            $cursoUUID->bytes,
            array('certificado')
        );

        if ( ! $idCertificadoAtivo['certificado'] ) {

            throw new cassandra_NotFoundException();

        }

        $idCertificadoAtivo = UUID::import( $idCertificadoAtivo['certificado'] )->bytes;

        $column = $this->_cf->get( $idCertificadoAtivo );

        return $this->_criarFromCassandra($column, $curso);
    }


    /**
     * @param mixed $de
     * @param mixed $ate
     * @param array|null $filtros
     * @return array
     */
    public function recuperarTodos($de = '', $ate = '', array $filtros = null)
    {
        if ($filtros['count']) {
            $count = $filtros['count'];
        } else {
            $count = CertificadoDAO::MAX_CERTIFICADOS;
        }

        if (isset($filtros['curso']) && $filtros['curso'] instanceof WeLearn_Cursos_Curso) {
            return $this->recuperarTodosPorCurso($filtros['curso'], $de, $ate, $count);
        }

        if (isset($filtros['aluno']) && $filtros['aluno'] instanceof WeLearn_Usuarios_Aluno) {
            return $this->recuperarTodosPorAluno($filtros['aluno'], $de, $ate, $count);
        }

        return array();
    }

    /**
     * @param WeLearn_Cursos_Curso $curso
     * @param string $de
     * @param string $ate
     * @param int $count
     * @return array
     */
    public function recuperarTodosPorCurso(
        WeLearn_Cursos_Curso $curso,
        $de = '',
        $ate = '',
        $count = CertificadoDAO::MAX_CERTIFICADOS
    )
    {
        if ($de != '') {
            $de = UUID::import( $de )->bytes;
        }

        if ($ate != '') {
            $ate = UUID::import( $ate )->bytes;
        }

        $cursoUUID = UUID::import( $curso->getId() );

        $idsCertificados = array_keys(
            $this->_certificadosPorCursoCF->get(
                $cursoUUID->bytes,
                null,
                $de,
                $ate,
                true,
                $count
            )
        );

        $columns = $this->_cf->multiget( $idsCertificados );

        return $this->_criarVariosFromCassandra( $columns, $curso );
    }

    /**
     * @param WeLearn_Usuarios_Aluno $aluno
     * @param string $de
     * @param string $ate
     * @param int $count
     * @return array
     */
    public function recuperarTodosPorAluno(WeLearn_Usuarios_Aluno $aluno,
                                           $de = '',
                                           $ate = '',
                                           $count = 20)
    {
        if ($de != '') {
            $de = UUID::import( $de )->bytes;
        }

        if ($ate != '') {
            $ate = UUID::import( $ate )->bytes;
        }

        $idsCertificados = array_keys(
            $this->_certificadosPorAlunoCF->get(
                $aluno->getId(),
                null,
                $de,
                $ate,
                true,
                $count
            )
        );

        try {

            $columns = $this->_cf->multiget( $idsCertificados );

        } catch ( cassandra_NotFoundException $e ) {

            $columns = $this->_recuperarRestantesPorAluno( $idsCertificados, $aluno );

        }

        return $this->_criarVariosFromCassandra( $columns );
    }

    /**
     * @param mixed $de
     * @param mixed $ate
     * @return int
     */
    public function recuperarQtdTotal($de = null, $ate = null)
    {
        if ( $de instanceof WeLearn_Cursos_Curso ) {
            return $this->recuperarQtdTotalPorCurso( $de );
        }

        if ( $de instanceof WeLearn_Usuarios_Aluno ) {
            return $this->recuperarQtdTotalPorAluno( $de );
        }

        return 0;
    }

    /**
     * @param WeLearn_Cursos_Curso $curso
     * @return int
     */
    public function recuperarQtdTotalPorCurso(WeLearn_Cursos_Curso $curso)
    {
        $cursoUUID = UUID::import( $curso->getId() );

        return $this->_certificadosPorCursoCF->get_count( $cursoUUID->bytes );
    }

    /**
     * @param WeLearn_Usuarios_Aluno $aluno
     * @return int
     */
    public function recuperarQtdTotalPorAluno(WeLearn_Usuarios_Aluno $aluno)
    {
        return $this->_certificadosPorAlunoCF->get_count( $aluno->getId() );
    }

    /**
     * @param mixed $id
     * @return WeLearn_DTO_IDTO
     */
    public function remover($id)
    {
        $certificadoRemovido = $this->recuperar( $id );

        $cursoUUID = UUID::import( $certificadoRemovido->getCurso()->getId() );
        $UUID = UUID::import( $id );

        $this->_cf->remove( $UUID->bytes );
        $this->_certificadosPorCursoCF->remove(
            $cursoUUID->bytes,
            array($UUID->bytes)
        );

        if ( $certificadoRemovido->isAtivo() ) {
            $this->_cursoDao->getCf()->insert(
                $cursoUUID->bytes,
                array( 'certificado' => '' )
            );
        }

        $certificadoRemovido->setPersistido(false);

        return $certificadoRemovido;
    }

    /**
     * @param WeLearn_Cursos_Curso $curso
     * @return array
     */
    public function removerTodosPorCurso(WeLearn_Cursos_Curso $curso)
    {
        $cursoUUID = UUID::import( $curso->getId() );

        $listaRemovidos = $this->recuperarTodosPorCurso( $curso );

        foreach ($listaRemovidos as $certificado) {
            $UUID = UUID::import( $certificado->getId() );

            $this->_cf->remove($UUID->bytes);
            $certificado->setPersistido( false );
        }

        $this->_cursoDao->getCf()->insert(
            $cursoUUID->bytes,
            array( 'certificado' => '' )
        );

        $this->_certificadosPorCursoCF->remove( $cursoUUID->bytes );

        return $listaRemovidos;
    }

    /**
     * @param array|null $dados
     * @return WeLearn_DTO_IDTO
     */
    public function criarNovo(array $dados = null)
    {
        return new WeLearn_Cursos_Certificado( $dados );
    }

    /**
     * @param WeLearn_DTO_IDTO $dto
     * @return boolean
     */
    protected function _atualizar(WeLearn_DTO_IDTO $dto)
    {
        $UUID = UUID::import( $dto->getId() );

        $this->_cf->insert( $UUID->bytes, $dto->toCassandra() );

        $this->alterarAtivo( $dto );
    }


    /**
     * @param WeLearn_DTO_IDTO $dto
     * @return boolean
     */
    protected function _adicionar(WeLearn_DTO_IDTO &$dto)
    {
        if ( ! $dto->getId() ) {
            $UUID = UUID::mint();
            $dto->setId( $UUID->string );
        } else {
            $UUID = UUID::import( $dto->getId() );
        }

        $cursoUUID = UUID::import( $dto->getCurso()->getId() );

        $this->_cf->insert( $UUID->bytes, $dto->toCassandra() );
        $this->_certificadosPorCursoCF->insert(
            $cursoUUID->bytes,
            array($UUID->bytes => '')
        );

        $this->alterarAtivo( $dto );

        $dto->setPersistido(true);
    }

    /**
     * @param WeLearn_Cursos_Certificado $certificado
     * @return void
     */
    public function alterarAtivo(WeLearn_Cursos_Certificado $certificado)
    {
        try {
            $cursoUUID = UUID::import( $certificado->getCurso()->getId() );

            $idCertificadoAtivo = $this->_cursoDao->getCf()->get(
                $cursoUUID->bytes,
                array('certificado')
            );
            $idCertificadoAtivo = $idCertificadoAtivo['certificado'];
        } catch (cassandra_NotFoundException $e) {
            $idCertificadoAtivo = '';
        }

        if ( $certificado->isAtivo() ) {

            if ( $idCertificadoAtivo != $certificado->getId() ) {

                $this->_cursoDao->getCf()->insert(
                    $cursoUUID->bytes,
                    array( 'certificado' => $certificado->getId() )
                );

                if ( $idCertificadoAtivo ) {
                    $UUIDInativo = UUID::import($idCertificadoAtivo)->bytes;
                    $this->_cf->insert( $UUIDInativo, array( 'ativo' => 'false' ) );
                }

            }

        } else {

            if ( $idCertificadoAtivo == $certificado->getId() ) {
                $this->_cursoDao->getCf()->insert(
                    $cursoUUID->bytes,
                    array( 'certificado' => '' )
                );
            }

        }
    }

    private function _criarFromCassandra (array $column, WeLearn_Cursos_Curso $cursoPadrao = null)
    {
        $column['curso'] = ($cursoPadrao instanceof WeLearn_Cursos_Curso)
                           ? $cursoPadrao
                           : $this->_cursoDao->recuperar( $column['curso'] );

        $column['ativo'] = ($column['ativo'] == 'true') ? true : false;

        $certificado = $this->criarNovo();
        $certificado->fromCassandra( $column );

        return $certificado;
    }

    private function _criarVariosFromCassandra (array $columns, WeLearn_Cursos_Curso $cursoPadrao = null)
    {
        $listaCertificados = array();

        foreach ($columns as $column) {
            $listaCertificados[] = $this->_criarFromCassandra($column, $cursoPadrao);
        }

        return $listaCertificados;
    }

    private function _recuperarRestantesPorAluno(array $ids, WeLearn_Usuarios_Aluno $aluno)
    {
        $columns = array();

        for ($i = 0; $i < count( $ids ); $i++) {

            try {

                $column = $this->_cf->get( $ids[$i] );

                $columns[] = $column;

            } catch ( cassandra_NotFoundException $e ) {

                $this->_certificadosPorAlunoCF->remove(
                    $aluno->getId(),
                    array( $ids[$i] )
                );

            }

        }

        return $columns;
    }
}
