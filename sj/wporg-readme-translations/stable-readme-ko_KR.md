# ko_KR - Stable Readme Translation Draft

## Short Description

AI 지원 WordPress 작업을 위한 거버넌스, 승인, 커밋 사전 점검, 감사 로그.

## Description

Npcink Governance Core는 WordPress 작업을 위한 Npcink AI 거버넌스 계층입니다. AI 지원 작업이 WordPress 사이트에 커밋되기 전에 사이트 관리자와 신뢰할 수 있는 host 플러그인이 검토할 수 있는 승인 계층을 제공합니다.

Core는 사용 가능한 WordPress Abilities API 작업을 발견하고, AI가 제안한 작업을 기록하며, 승인과 거부를 지원하고, commit preflight 컨텍스트를 반환하며, 감사 증거를 저장합니다. AI 도구, adapter 또는 제품 플러그인이 WordPress 변경을 요청할 수 있지만 사이트에는 명확한 거버넌스 기록이 필요한 환경에 적합합니다.

### Core가 하는 일

- 거버넌스 intake에 사용할 수 있는 WordPress abilities를 발견합니다.
- AI 지원 WordPress 작업에 대한 proposal을 저장합니다.
- 감사 증거와 함께 approve 및 reject 결정을 지원합니다.
- 신뢰할 수 있는 host 또는 adapter가 최종 쓰기를 수행하기 전에 commit-preflight 컨텍스트를 제공합니다.
- 신뢰할 수 있는 거버넌스 클라이언트를 위한 scoped app keys를 관리합니다.
- proposal 생성, policy evaluation, approval, rejection, commit preflight, execution-result handoff 감사 이벤트를 기록합니다.
- 거버넌스를 콘텐츠 생성, 모델 라우팅, 클라우드 서비스 과금, 최종 쓰기 실행과 분리합니다.

### 이 플러그인의 대상

Npcink Governance Core는 AI 지원 WordPress 작업에 대해 로컬 승인 및 감사 경계가 필요한 WordPress 관리자, host 플러그인, adapter, 개발자를 위한 플러그인입니다. AI 도구가 변경을 준비하거나 요청할 수 있지만 사이트 소유자 또는 신뢰할 수 있는 거버넌스 클라이언트가 해당 변경을 검토, 승인, 추적해야 하는 경우에 유용합니다.

이 플러그인은 원클릭 AI 작성 도구, SEO 도우미, 이미지 생성기, 챗봇 또는 워크플로 runtime이 아닙니다. 제품 워크플로와 ability callbacks는 별도의 provider, adapter 또는 제품 플러그인에 속해야 합니다.

### 요구 사항 및 통합

Core는 WordPress 7.0 이상 및 WordPress Abilities API providers와 함께 사용할 때 가장 적합합니다. 1차 참조 provider는 Npcink Abilities Toolkit이지만, 안정적인 ability ids, schemas, permission callbacks, risk metadata, dry-run previews를 제공하는 타사 WordPress Abilities API providers도 기본 거버넌스 라이프사이클에서 다룰 수 있습니다. 참조 채널 adapter는 Npcink AI Client Adapter이며, Core commit preflight 이후 WordPress Abilities API를 통해 승인된 abilities를 실행합니다.

Core는 `/wp-json/npcink-governance-core/v1/` 아래에 거버넌스 REST endpoints를 제공합니다. 신뢰할 수 있는 adapters와 host 플러그인은 이 endpoints를 사용해 proposals를 만들고, 승인 또는 거부하고, commit preflight를 요청하고, 외부 실행 결과를 기록할 수 있습니다.

### 개인정보 및 데이터

Npcink Governance Core는 외부 AI 서비스를 호출하지 않고, 원격 자산을 로드하지 않으며, 사이트 데이터를 제3자에게 보내지 않습니다. proposal data, audit events, app-key metadata, rate-limit state를 포함한 거버넌스 기록을 WordPress 데이터베이스 테이블에 로컬로 저장합니다. App-key secrets는 해시되어 저장되며, 일회용 bearer tokens는 생성 시에만 표시됩니다.

### 경계

Core는 콘텐츠를 생성하지 않고, 모델을 라우팅하지 않으며, MCP 또는 workflow runtimes를 실행하지 않고, provider credentials를 저장하지 않으며, ability execution을 프록시하지 않고, 최종 WordPress 쓰기를 수행하지 않습니다. 최종 쓰기는 거버넌스 단계가 완료된 후 Core 외부의 신뢰할 수 있는 host, adapter 또는 runtime이 담당합니다.

## Installation

1. 플러그인을 `wp-content/plugins/npcink-governance-core`에 업로드합니다.
2. WordPress에서 Npcink Governance Core를 활성화합니다.
3. Npcink AI > Core를 열어 거버넌스 상태, proposals, audit entries, 고급 Core app-key 제어를 검토합니다.

## FAQ

### 이 플러그인은 누구를 위한 것인가요?

AI 지원 WordPress 작업에 대해 검토 가능한 거버넌스가 필요한 사이트 관리자, host 플러그인, adapter, 개발자를 위한 것입니다. AI 도구가 변경을 준비할 수 있지만 적용 전에 승인, commit preflight, 감사 증거가 필요한 경우 특히 유용합니다.

### AI 콘텐츠 생성기인가요?

아니요. Core는 글을 작성하거나, 미디어를 생성하거나, SEO 문구를 만들거나, 댓글에 답변하거나, AI 모델을 선택하거나, provider credentials를 저장하지 않습니다. 별도 도구, adapter 또는 WordPress Abilities API provider가 만든 proposed operations를 거버넌스합니다.

### Core가 AI 쓰기를 실행하나요?

아니요. Core는 거버넌스 proposals를 기록하고 commit-preflight 컨텍스트를 반환합니다. 최종 쓰기는 Core 외부의 신뢰할 수 있는 host, adapter 또는 runtime이 담당합니다.

### proposal이란 무엇인가요?

proposal은 AI 지원 WordPress 작업에 대한 저장된 요청입니다. 대상 ability, 입력 요약, preview 또는 dry-run evidence, 상태, caller metadata, 검토에 필요한 audit trail을 포함합니다.

### commit preflight란 무엇인가요?

commit preflight는 승인 후, 신뢰할 수 있는 외부 구성 요소가 최종 쓰기를 수행하기 전에 실행되는 거버넌스 검사입니다. 하위 구성 요소가 승인된 요청에 따라 동작하는지 검증할 수 있도록 제한된 컨텍스트, correlation data, input binding을 반환합니다.

### abilities를 위해 다른 플러그인이 필요한가요?

전체 ability intake에는 필요합니다. Core는 WordPress Abilities API providers가 노출하는 abilities를 거버넌스합니다. Npcink Abilities Toolkit은 참조 provider이며, 타사 providers도 안정적인 ability ids, schemas, permissions, risk metadata, dry-run previews를 제공해 통합할 수 있습니다.

### 이 플러그인이 외부 AI 서비스로 데이터를 보내나요?

아니요. Core는 외부 AI 서비스를 호출하지 않으며 사이트 데이터를 제3자에게 보내지 않습니다. 거버넌스 기록은 WordPress 데이터베이스 테이블에 로컬로 저장됩니다.

### Core는 어떤 데이터를 저장하나요?

Core는 proposal records, approval 및 rejection decisions, commit-preflight evidence, execution-result handoff records, audit events, app-key metadata, rate-limit state를 저장합니다. App-key secrets는 해시로 저장되며, 일회용 bearer tokens는 생성 시에만 표시됩니다.

### scoped app keys는 무엇에 사용되나요?

scoped app keys는 신뢰할 수 있는 거버넌스 클라이언트가 광범위한 관리자 권한 없이 특정 Core REST endpoints를 호출할 수 있게 합니다. 제어된 hosts, adapters, 내부 거버넌스 클라이언트를 위한 것입니다.

### OpenClaw가 Core에 직접 연결해야 하나요?

제품화된 OpenClaw 설정은 신뢰할 수 있는 adapter를 통해 연결해야 합니다. 직접 Core app keys는 내부 거버넌스 클라이언트와 fallback testing에만 사용해야 합니다.

### 타사 ability providers가 Core를 사용할 수 있나요?

예. 기본 proposal lifecycle은 provider-neutral입니다. 타사 providers는 schemas, permission callbacks, risk metadata, dry-run previews가 포함된 WordPress Abilities API definitions를 노출한 뒤 쓰기 또는 파괴적 작업을 Core 검토에 제출할 수 있습니다.

### Core가 WordPress 7.0을 요구하는 이유는 무엇인가요?

Core의 ability intake는 WordPress Abilities API 인터페이스를 읽으며, Core의 거버넌스 계약은 WordPress 7.0 이상을 기준으로 구축되고 테스트되었습니다. 더 오래된 WordPress에서는 ability intake가 테스트되지 않았으며, Core는 실제 ability ids와 대조할 수 없는 proposals를 거버넌스하지 않고 fail closed(거부)합니다.

### Core는 멀티사이트(multisite)를 지원하나요?

지원합니다. 네트워크 활성화(network activation) 시 모든 기존 사이트에 Core 거버넌스 테이블을 생성하며, 새 사이트는 초기화 시 생성됩니다. 거버넌스 기록은 사이트별로 저장됩니다.

### 플러그인을 비활성화하거나 삭제하면 거버넌스 기록은 어떻게 되나요?

비활성화해도 모든 거버넌스 기록은 유지됩니다. 플러그인을 삭제해도 기록은 유지됩니다. 승인 및 감사 이력이 사이트 보존 요건의 대상이 될 수 있기 때문에 proposals, audit events, app-key metadata, read authorization records는 보존됩니다. 이 릴리스에는 자동 언인스톨 삭제가 없습니다.

## Upgrade Notice

### 0.3.0

운영자 경험 및 REST 소비자 계약 릴리스입니다. 데이터 마이그레이션은 필요 없으며, 거버넌스 테이블은 0.2.0 이후 변경되지 않았습니다. 검토 대기열 필터는 판정 후에도 유지되고, 목록 endpoints는 총 개수를 반환하며, REST 타임스탬프는 ISO8601 UTC이고, 완전한 zh_CN 카탈로그가 포함되어 있습니다.

## Changelog

### 0.3.0

운영자 경험 및 REST 소비자 계약 릴리스. 관리 화면 검토 대기열은 판정 사이에 필터와 페이지 컨텍스트를 유지하고, 각 판정 후 다음 pending proposal을 열 수 있으며, ability와 대기 시간으로 필터링할 수 있고, 일회용 client token은 일반 관리 화면 안에 복사 버튼과 함께 표시됩니다. Audit read-requests에 offset과 search 페이징이 추가되었고, 모든 목록 endpoints는 총 개수를 반환하며, REST 타임스탬프는 ISO8601 UTC이고, 반복된 commit preflight는 복구를 위해 원래 handoff 식별자를 반환하며, 만료된 app keys는 전용 rotate-key 오류를 반환하고, 속도 제한 응답은 Retry-After 안내를 담으며, 런타임 contract endpoint는 옵트인 contract:read app scope를 허용합니다. 번들 zh_CN 카탈로그가 완성되었고(810 문자열) 로컬로 배포되는 플러그인 zip에서도 로드됩니다. 이 릴리스도 최종 쓰기 실행, workflow runtime, provider credentials 소유는 추가하지 않습니다.

### 0.2.0

0.1.1 이후 완료된 거버넌스 intake, approval, preflight, audit, 릴리스 패키징 강화 릴리스 후보. 이 릴리스는 최종 쓰기 실행이나 workflow runtime 소유를 추가하지 않습니다.

### 0.1.1

일반 RC tag 경로를 위한 릴리스 도구 패치. 버전 매트릭스는 이제 역사적 릴리스에 다시 태그하지 않고도 현재 커밋을 가리키는 annotated release tags를 인식합니다.

### 0.1.0

ability intake, proposals, approval/rejection, commit preflight, scoped app keys, rate limiting, audit records를 포함한 초기 거버넌스 플러그인입니다.
