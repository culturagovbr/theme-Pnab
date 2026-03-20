# Componente `<mc-teleport-multiple>`

Wrapper em torno do [**Teleport**](https://vuejs.org/guide/built-ins/teleport.html) do Vue 3 com painel de bloqueio reutilizável (overlay escuro, spinner e área de mensagem). O componente **não contém textos de negócio**: títulos e passos vêm sempre do componente pai via props.

**Tema:** Pnab (`src/themes/Pnab`). Estilos em `_mc-teleport-multiple.scss` (importado em `theme-Pnab.scss`).

## Propriedades

- *String \| Object **to** = `'body'`* — Alvo do Vue Teleport (seletor CSS, ex. `body`, ou elemento DOM).
- *Boolean **disabled** = false* — Se `true`, o Teleport não move o conteúdo (renderiza no lugar do componente).
- *Boolean **show** = false* — Exibe ou oculta overlay + painel (spinner e mensagens).
- *String **message** = ''* — Texto único; ignorado se `messages` tiver pelo menos uma string não vazia.
- *Array **messages** = []* — Lista de strings (vazias são descartadas). Com **2+** itens válidos e `messageStepMs > 0`, as mensagens alternam enquanto `show` for `true`.
- *Number **messageStepMs** = 3500* — Intervalo em ms entre trocas. Valores inválidos ou negativos usam o default **`3500`**. Use **`0`** para não rotacionar (fica no primeiro passo).
- *Boolean **blockInteraction** = true* — Bloqueia clique/toque no overlay; com `false`, o overlay usa `pointer-events: none` (interação passa para o que está atrás).
- *Number **fadeDurationMs** = 320* — Duração do fade entre mensagens (ms).

## Comportamento

- **Prioridade de texto:** se `messages` tiver ao menos uma string não vazia, só elas são usadas; caso contrário usa-se `message`.
- **Rotação:** com dois ou mais passos e `messageStepMs > 0`, o índice avança em loop enquanto `show` for `true`. Ao abrir (`show` → `true`), o índice volta a `0`. O timer é limpo ao fechar e no `unmounted`.
- **Layout:** largura do painel e altura da faixa de texto são fixas em termos de linhas (ver SCSS), para a troca de mensagens não redimensionar o cartão.
- **Transição:** troca de passos usa `<transition mode="out-in">` com opacidade.

## Acessibilidade

- Overlay com `role="status"`, `aria-live="polite"` e `aria-busy="true"` enquanto visível.
- Spinner com `aria-hidden="true"`.

## Estilos

Classes BEM com prefixo `mc-teleport-multiple__`. Transição usa a variável CSS `--mc-teleport-multiple-fade-duration` (definida inline a partir de `fadeDurationMs`).

Para personalizar cores, largura máxima do painel ou tipografia, sobrescreva no SASS do tema ou em folha específica **após** o import de `_mc-teleport-multiple.scss`.

## Eventos

Nenhum (`emits` vazio por desenho).

## Slots

Nenhum. Conteúdo é 100% dirigido por props (componente intencionalmente fechado para manter o contrato simples).

### Importando o componente

```php
<?php
$this->import('mc-teleport-multiple');
?>
```

### Exemplos de uso

```html
<!-- Uma mensagem, bloqueio total (default) -->
<mc-teleport-multiple
    to="body"
    :show="loading"
    :message="text('Aguarde…')"
></mc-teleport-multiple>
```

```html
<!-- Vários passos, intervalo e fade customizados -->
<mc-teleport-multiple
    to="body"
    :show="loading"
    :messages="[
        text('Primeira etapa…'),
        text('Segunda etapa…'),
    ]"
    :message-step-ms="4000"
    :fade-duration-ms="400"
    :block-interaction="true"
></mc-teleport-multiple>
```

```html
<!-- Overlay visual sem capturar cliques -->
<mc-teleport-multiple
    to="body"
    :show="hint"
    message="Processando em segundo plano…"
    :block-interaction="false"
></mc-teleport-multiple>
```

```html
<!-- Várias mensagens mas sem rotação automática -->
<mc-teleport-multiple
    to="body"
    :show="loading"
    :messages="['A', 'B', 'C']"
    :message-step-ms="0"
></mc-teleport-multiple>
```

### Constante (script)

O intervalo padrão entre passos no código é **`3500`** ms (`DEFAULT_MESSAGE_STEP_MS` em `script.js`), alinhado à prop `messageStepMs`.
