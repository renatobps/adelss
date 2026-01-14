@extends('layouts.porto')

@section('title', 'Como Cadastrar Vários Membros de Uma Vez?')

@section('page-title', 'Importação de Membros')

@section('breadcrumbs')
    <li><a href="{{ route('members.index') }}">Membros</a></li>
    <li><span>Importar CSV</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <!-- Banner de Destaque -->
        <div class="alert alert-success mb-4 d-flex justify-content-between align-items-center">
            <div>
                <i class="bx bx-bulb me-2"></i>
                <strong>Como Cadastrar Vários Membros de Uma Vez?</strong>
            </div>
            <a href="#video-tutorial" class="btn btn-light btn-sm">
                <i class="bx bx-help-circle"></i> Ver Tutorial
            </a>
        </div>

        <!-- Passo 1: Download do Template -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 1.2rem; font-weight: bold;">1</div>
                    <div class="flex-grow-1">
                        <h5 class="mb-2">Baixe o Template CSV</h5>
                        <p class="text-muted mb-3">Primeiro, baixe nosso arquivo de exemplo com o formato correto:</p>
                        <a href="{{ route('members.import.template') }}" class="btn btn-success">
                            <i class="bx bx-download me-2"></i>Baixar Template (Arquivo de Exemplo)
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Passo 2: Preencher os Dados -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 1.2rem; font-weight: bold;">2</div>
                    <div class="flex-grow-1">
                        <h5 class="mb-2">Preencha os Dados</h5>
                        <p class="text-muted mb-3">Abra o arquivo baixado no Excel, Google Sheets ou LibreOffice:</p>
                        
                        <!-- Tabela de Exemplo -->
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>nome</th>
                                        <th>email</th>
                                        <th>telefone</th>
                                        <th>status</th>
                                        <th>genero</th>
                                        <th>estado_civil</th>
                                        <th>data_nascimento</th>
                                        <th>data_membresia</th>
                                        <th>cargo_id</th>
                                        <th>departamento_id</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>João Silva</td>
                                        <td>joao@email.com</td>
                                        <td>11999999999</td>
                                        <td>ativo</td>
                                        <td>M</td>
                                        <td>casado</td>
                                        <td>1990-05-15</td>
                                        <td>2020-01-10</td>
                                        <td>1</td>
                                        <td>1</td>
                                    </tr>
                                    <tr>
                                        <td>Maria Santos</td>
                                        <td>maria@email.com</td>
                                        <td>11988888888</td>
                                        <td>ativo</td>
                                        <td>F</td>
                                        <td>solteiro</td>
                                        <td>1995-08-20</td>
                                        <td>2021-03-05</td>
                                        <td>1</td>
                                        <td>1,2</td>
                                    </tr>
                                    <tr>
                                        <td>Pedro Oliveira</td>
                                        <td></td>
                                        <td>11977777777</td>
                                        <td>visitante</td>
                                        <td>M</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>2</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Regras Importantes -->
                        <div class="alert alert-info">
                            <strong>Regras importantes:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>nome:</strong> Obrigatório - Nome completo (ex: João Silva)</li>
                                <li><strong>email:</strong> Opcional, mas se preencher deve ser válido</li>
                                <li><strong>telefone:</strong> Opcional, com DDD, apenas números (ex: 11999999999)</li>
                                <li><strong>status:</strong> Opcional - Use: ativo, inativo, visitante ou membro_transferido (padrão: ativo)</li>
                                <li><strong>genero:</strong> Opcional - Use: M (Masculino) ou F (Feminino)</li>
                                <li><strong>estado_civil:</strong> Opcional - Use: solteiro, casado, divorciado, viuvo ou uniao_estavel</li>
                                <li><strong>data_nascimento:</strong> Opcional - Formato: YYYY-MM-DD ou DD/MM/YYYY (ex: 1990-05-15)</li>
                                <li><strong>data_membresia:</strong> Opcional - Formato: YYYY-MM-DD ou DD/MM/YYYY (ex: 2020-01-10)</li>
                                <li><strong>cargo_id:</strong> Opcional - ID do cargo (verifique na página de Cargos)</li>
                                            <li><strong>departamento_id:</strong> Opcional - ID(s) do(s) departamento(s). Para múltiplos, separe por vírgula (ex: 1,2,3)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Passo 3: Salvar como CSV -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 1.2rem; font-weight: bold;">3</div>
                    <div class="flex-grow-1">
                        <h5 class="mb-2">Salve como CSV</h5>
                        <ul class="mb-0">
                            <li><strong>Excel:</strong> Arquivo → Salvar Como → CSV (separado por vírgulas)</li>
                            <li><strong>Google Sheets:</strong> Arquivo → Fazer download → CSV</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Passo 4: Importar o Arquivo -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 1.2rem; font-weight: bold;">4</div>
                    <div class="flex-grow-1">
                        <h5 class="mb-2">Importe o Arquivo</h5>
                        <p class="mb-3">Clique no botão verde no topo da página:</p>
                        <a href="{{ route('members.index') }}" class="btn btn-success mb-3">
                            <i class="bx bx-upload me-2"></i>Ir para Importação
                        </a>
                        <p class="mb-2"><strong>Depois:</strong></p>
                        <ol>
                            <li>Escolha o arquivo CSV que você salvou</li>
                            <li>Clique em "Importar"</li>
                            <li>Aguarde o resultado: "Sucessos: X, Erros: Y"</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensagem de Sucesso -->
        <div class="alert alert-success">
            <i class="bx bx-check-circle me-2"></i>
            <strong>Pronto!</strong> Todos os membros válidos foram cadastrados no sistema.
        </div>
    </div>

    <!-- Sidebar Direita -->
    <div class="col-lg-4">
        <!-- Dicas Rápidas -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="bx bx-star me-2"></i>★ Dicas Rápidas</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bx bx-check text-success me-2"></i>
                        Cadastre centenas de membros em segundos
                    </li>
                    <li class="mb-2">
                        <i class="bx bx-check text-success me-2"></i>
                        O sistema valida tudo automaticamente
                    </li>
                    <li class="mb-2">
                        <i class="bx bx-check text-success me-2"></i>
                        Telefones duplicados são ignorados
                    </li>
                    <li class="mb-2">
                        <i class="bx bx-check text-success me-2"></i>
                        Você recebe relatório de erros detalhado
                    </li>
                    <li class="mb-2">
                        <i class="bx bx-error text-warning me-2"></i>
                        Não precisa formatar o telefone (pode ter parênteses, traços, etc)
                    </li>
                    <li class="mb-2">
                        <i class="bx bx-error text-warning me-2"></i>
                        Email pode ficar vazio
                    </li>
                    <li class="mb-2">
                        <i class="bx bx-error text-warning me-2"></i>
                        Verifique o ID da categoria antes de importar
                    </li>
                </ul>
            </div>
        </div>

        <!-- Vídeo Tutorial -->
        <div class="card mb-4" id="video-tutorial">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-video me-2"></i>Vídeo Tutorial</h6>
            </div>
            <div class="card-body text-center">
                <div class="bg-light rounded p-5 mb-3">
                    <i class="bx bx-play-circle text-danger" style="font-size: 4rem;"></i>
                </div>
                <p class="text-muted mb-0">Em breve: vídeo passo a passo</p>
            </div>
        </div>

        <!-- Erros Comuns -->
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bx bx-error me-2"></i>▲ Erros Comuns</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bx bx-x text-danger me-2"></i>
                        Telefone inválido: Use 10+ dígitos
                    </li>
                    <li class="mb-2">
                        <i class="bx bx-x text-danger me-2"></i>
                        Email inválido: Formato email@dominio.com
                    </li>
                    <li class="mb-2">
                        <i class="bx bx-x text-danger me-2"></i>
                        Telefone duplicado: Já existe no sistema
                    </li>
                    <li class="mb-2">
                        <i class="bx bx-x text-danger me-2"></i>
                        Nome vazio: Campo obrigatório
                    </li>
                    <li class="mb-2">
                        <i class="bx bx-x text-danger me-2"></i>
                        Categoria inválida: Verifique o ID na página de Categorias
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
