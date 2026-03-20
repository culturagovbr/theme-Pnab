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
- **`loadParExercicios`** — Se `true` e a lista ainda estiver vazia após prop + PHP, faz `GET aldirblanc/parExercicios?federativeEntityId=` (id tem de coincidir com a sessão no servidor).

## Origem dos dados (ordem)

1. Prop **`exercicios`** (se não vazia).
2. **`$MAPAS.config.mcFederativeEntityPar.exercicios`** (definido em `init.php` ao importar o componente).
3. Resposta do **GET** acima, quando **`loadParExercicios`** está ativo.

O **`$MAPAS.config.aldirblanc.selectedFederativeEntityId`** é exposto pelo hook do plugin AldirBlanc e serve para o fallback HTTP.

## Integração no projeto

- **Criar oportunidade:** `create-opportunity/template.php` importa este componente e usa `load-par-exercicios` sem passar `exercicios`.
- **Editar / ver cabeçalho:** `opportunity-basic-info` usa `readonly` + `load-par-exercicios` quando a oportunidade já tem ids PAR.

## Método útil

- **`validate()`** (via `ref`) — Marca erros visíveis e devolve `true` se os quatro níveis estão coerentes com a hierarquia carregada.

## Dependências

- Plugin **AldirBlanc**: `FederativeEntityService`, `GET_parExercicios`, metadados `parExercicioId` / `parMetaId` / `parAcaoId` / `parAtividadeId` em `Opportunity`.
- Estilos: `_create-opportunity-par.scss` (classes `mc-federative-entity-par*`).
