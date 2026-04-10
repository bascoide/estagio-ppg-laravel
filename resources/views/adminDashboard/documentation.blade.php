@extends('layouts.admin')

@section('content')
<div class="flex-grow h-screen p-4 bg-white rounded shadow-md overflow-y-scroll">

    <h1 id="topo" class="text-3xl font-bold text-blue-800 mb-6">Documentação da Interface de Administração</h1>

    <h2 class="text-xl font-bold text-blue-700 mt-8 mb-2">Índice</h2>
    <ul class="list-disc ml-6 mb-6 text-blue-600">
        <li><a href="#status" class="hover:underline">1. Status dos Documentos</a></li>
        <li><a href="#sidebar" class="hover:underline">2. Barra lateral</a></li>
        <ul class="list-disc ml-6 mb-6 text-blue-600">
            <li><a href="#protocolos" class="hover:underline">2.1. Protocolos</a></li>
            <li><a href="#documentos" class="hover:underline">2.2. Documentos</a></li>
            <li><a href="#cadastros" class="hover:underline">2.3. Cadastros</a></li>
            <li><a href="#professores" class="hover:underline">2.4. Professores</a></li>
        </ul>
        <label class="text-gray-700">Parte mais técnica: </label>
        <li><a href="#preenchimento" class="hover:underline">3. Regras de preenchimento de documentos</a></li>
        <ul class="list-disc ml-6 mb-6 text-blue-600">
            <li><a href="#tipos-campos" class="hover:underline">3.1. Tipos de campos</a></li>
            <li><a href="#syntax" class="hover:underline">3.2. Sintaxe</a></li>
        </ul>
    </ul>

    <hr class="my-6 border-gray-300">

    <h1 id="status" class="text-xl font-bold text-gray-800 mt-8 mb-2">1. Status dos Documentos</h1>
    <ul class="list-disc ml-6 mb-6">
        <li><span class="text-yellow-600 font-semibold">Pendente</span> – O documento está aguardando revisão ou ação do administrador.</li>
        <li><span class="text-red-500 font-semibold">Recusado</span> – O documento foi rejeitado e precisa ser revisto pelo aluno.</li>
        <li><span class="text-green-500 font-semibold">Aceito</span> – O documento foi aprovado e precisa ser assinado pelo aluno.</li>
        <li><span class="text-yellow-800 font-semibold">Por validar</span> – O documento foi reenviado e precisa ser revisto para prosseguir.</li>
        <li><span class="text-purple-800 font-semibold">Invalidado</span> – O documento está mal formatado ou não foi assinado.</li>
        <li><span class="text-cyan-500 font-semibold">Validado</span> – O documento está finalizado.</li>
        <li><span class="text-gray-600 font-semibold">Inativo</span> – Apenas usados para manter histórico.</li>
    </ul>

    <div class="text-right text-sm text-blue-500 hover:underline mb-8">
        <a href="#topo">⬆ Voltar ao topo</a>
    </div>

    <h1 id="sidebar" class="text-xl font-bold text-gray-800 mt-8 mb-2">2. Barra lateral</h1>

    <div class="ml-8">
        <h2 id="protocolos" class="text-xl font-bold text-blue-700 mt-8 mb-2">2.1. Protocolos</h2>
        <div class="bg-blue-100 border-l-4 border-blue-400 text-blue-800 p-4 rounded mb-4">
            <p>Gestão de protocolos com diferentes estados e ações administrativas.</p>
        </div>
        <h3 class="font-semibold text-gray-700">Estados:</h3>
        <ul class="list-disc ml-6 mb-4">
            <li><strong>Pendentes</strong>: Aguardando revisão. Transições: <em>Aceite</em> ou <em>Recusado</em></li>
            <li><strong>Por validar</strong>: Verificação de assinaturas. Transições: <em>Validado</em> ou <em>Invalidado</em></li>
            <li><strong>Validados</strong>: Processo concluído. Possível anular se necessário.</li>
        </ul>

        <div class="text-right text-sm text-blue-500 hover:underline mb-8">
            <a href="#topo">⬆ Voltar ao topo</a>
        </div>

        <h2 id="documentos" class="text-xl font-bold text-blue-700 mt-8 mb-2">2.2. Documentos</h2>
        <div class="bg-blue-100 border-l-4 border-blue-400 text-blue-800 p-4 rounded mb-4">
            <p>Área para gestão de documentos submetidos pelo administrador.</p>
        </div>
        <h3 class="font-semibold text-gray-700">Upload:</h3>
        <ul class="list-disc ml-6 mb-4">
            <li>Submissão de documentos <code>.docx</code>.</li>
            <li>Classificação como "Plano" ou "Protocolo".</li>
            <li>Associação com tipos de cursos.</li>
        </ul>

        <div class="text-right text-sm text-blue-500 hover:underline mb-8">
            <a href="#topo">⬆ Voltar ao topo</a>
        </div>

        <h2 id="cadastros" class="text-xl font-bold text-blue-700 mt-8 mb-2">2.3. Cadastros</h2>
        <div class="bg-blue-100 border-l-4 border-blue-400 text-blue-800 p-4 rounded mb-4">
            <p>Gestão de administradores, cursos e alunos.</p>
        </div>

        <div class="text-right text-sm text-blue-500 hover:underline">
            <a href="#topo">⬆ Voltar ao topo</a>
        </div>

        <h2 id="professores" class="text-xl font-bold text-blue-700 mt-8 mb-2">2.4. Professores</h2>
        <div class="bg-blue-100 border-l-4 border-blue-400 text-blue-800 p-4 rounded mb-4">
            <p>Gestão de documentos (relatórios) relacionados aos professores.</p>
        </div>

        <div class="text-right text-sm text-blue-500 hover:underline mb-8">
            <a href="#topo">⬆ Voltar ao topo</a>
        </div>
    </div>

    <h1 id="preenchimento" class="text-xl font-bold text-gray-800 mt-8 mb-2">3. Regras de preenchimento de documentos</h1>
    <div class="ml-8">
        <h2 id="tipos-campos" class="text-xl font-bold text-blue-700 mt-8 mb-2">3.1. Tipos de campos</h2>
        <ul class="list-disc ml-6 mb-4">
            <li>text, date, number, title, checkbox, email, radio, time, tel</li>
            <li>nif, nipc, group, course, professor, NA start, NA end</li>
        </ul>
        <div class="text-right text-sm text-blue-500 hover:underline mb-8">
            <a href="#topo">⬆ Voltar ao topo</a>
        </div>
        <h2 id="syntax" class="text-xl font-bold text-blue-700 mt-8 mb-2">3.2. Sintaxe</h2>
        <div class="bg-blue-100 border-l-4 border-blue-400 text-blue-800 p-4 rounded mt-2 mb-4">
            <p>&#123; nome do campo | tipo do campo &#125;</p>
        </div>
        <div class="text-right text-sm text-blue-500 hover:underline mb-8">
            <a href="#topo">⬆ Voltar ao topo</a>
        </div>
    </div>
</div>
@endsection
