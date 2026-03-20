# mc-federative-entity-par

Componente Vue do tema **Pnab** para o instrumento **PAR** no cadastro de oportunidades: cascata **Exercício → Meta → Ação → Atividade**, alinhada ao JSON da coluna `exercices` da entidade `FederativeEntity` (plugin AldirBlanc).

## Ficheiros

| Ficheiro | Função |
|----------|--------|
| `template.php` | Markup dos quatro níveis (modo edição e `readonly`). |
| `script.js` | Lógica, `v-model` dos ids PAR, validação e carregamento de dados. |
| `init.php` | Define `$MAPAS.config.mcFederativeEntityPar.exercicios` com `FederativeEntityService::getParExerciciosForSessionSelectedEntity()` (vazio se não houver gestor/ente na sessão). |
| `texts.php` | Chaves de tradução do grupo `mc-federative-entity-par`. |
| `README.md` | Este ficheiro. |

No `script.js`, as strings da UI vêm de `Utils.getTexts('mc-federative-entity-par')`, exposto ao template como **`translateMessage`** (evita o nome genérico `text`).

## Props

- **`exercicios`** — Lista no formato de `FederativeEntity.exercices`. Se vier preenchida, tem prioridade sobre a config global.
- **`modelValue`** — Objeto `{ parExercicioId, parMetaId, parAcaoId, parAtividadeId }` (strings; vazio = não selecionado). Usar `v-model`.
- **`emptyHint`** — Mensagem opcional quando não há opções (substitui o texto padrão).
- **`readonly`** — Só leitura: mostra rótulos resolvidos (ano, nomes), sem selects.
- **`loadParExercicios`** — Se `true` e a lista ainda estiver vazia após prop + PHP, faz `GET aldirblanc/parExercicios` (sem query: o servidor usa apenas o ente da sessão).

## Origem dos dados (ordem)

1. Prop **`exercicios`** (se não vazia).
2. **`$MAPAS.config.mcFederativeEntityPar.exercicios`** (definido em `init.php` ao importar o componente).
3. Resposta do **GET** acima, quando **`loadParExercicios`** está ativo.

## Integração no projeto

- **Criar oportunidade:** `create-opportunity/template.php` importa este componente e usa `load-par-exercicios` sem passar `exercicios`.
- **Usar modelo:** `opportunity-create-based-model` (modal «Título do edital») importa o mesmo bloco PAR e persiste os ids na oportunidade gerada via `Entity.save` após `generateOpportunity`.
- **Editar / ver cabeçalho:** `opportunity-basic-info` usa `readonly` + `load-par-exercicios` quando a oportunidade já tem ids PAR.

## Método útil

- **`validate()`** (via `ref`) — Marca erros visíveis e devolve `true` se os quatro níveis estão coerentes com a hierarquia carregada.

## Layout e largura

O root `.mc-federative-entity-par` usa **100% da largura do pai** (`width` / `max-width` / `min-width: 0` para não rebentar grelhas). Quem define colunas é o chamador (ex. `create-modal__fields--two-cols` nos modais «Criar oportunidade» e «Usar modelo»).

## Dependências

- Plugin **AldirBlanc**: `FederativeEntityService`, `GET_parExercicios`, metadados `parExercicioId` / `parMetaId` / `parAcaoId` / `parAtividadeId` em `Opportunity`.
- Estilos: `_create-opportunity-par.scss` (`.mc-federative-entity-par` + modais).
