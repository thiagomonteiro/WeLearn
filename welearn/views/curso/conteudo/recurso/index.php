<div id="recurso-index-content">
    <header>
        <hgroup>
            <h1>Recursos Extras do Curso</h1>
            <h3>Aqui é onde você encontra/gerencia os recursos
                extras contidos neste curso.</h3>
        </hgroup>
        <div>
            <p>
                Existem dois tipos de recursos extras, são eles:
                <strong>"Recursos Gerais"</strong> e <strong>"Recursos Restritos".</strong>
            </p>
            <ul>
                <li>
                    Os <strong>"Recursos Gerais"</strong> são acessíveis por
                    qualquer usuário inscrito no curso.
                </li>
                <li>
                    Os <strong>"Recursos Restritos"</strong> são acessíveis somente por aqueles
                    alunos que já passaram pela aula à que o recurso pertence.
                </li>
            </ul>
            <p>
                Escolha sua opção abaixo.
                <br><br>
                Ou <?php echo anchor('/curso/conteudo/recurso/criar/' . $idCurso,
                                     'Clique aqui para criar um novo recurso.') ?>
            </p>
        </div>
    </header>
    <div>
        <ul class="ul-tipos-recurso-simples">
            <li>
                <?php echo anchor('/curso/conteudo/recurso/geral/' . $idCurso,
                                  'Recursos Gerais') ?>
            </li>
            <li>
                <?php echo anchor('/curso/conteudo/recurso/restrito/' . $idCurso,
                                  'Recursos Restritos') ?>
            </li>
        </ul>
    </div>
</div>