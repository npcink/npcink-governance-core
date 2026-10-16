# Stack RC Release Notes — 2026-10-16

Status: engineering acceptance complete; publication remains separately
authorized. No snapshot tag was created or pushed.

## Candidate identity

- Name: `stack-rc-2026-10-16-core-0.3.0-adapter-0.4.3-toolkit-0.7.0`
- Matrix run: `2026-10-16T10:26:15+00:00`
- Exact-manifest acceptance completion observed:
  `2026-10-16T18:32:56+08:00`

The final clean 7/7 matrix used these exact revisions:

| Repository | Exact revision | Branch / state |
| --- | --- | --- |
| Core | `ed28f984153200a8362fcd0928bbb32b83636159` | clean topic branch |
| AI Cloud | `fe9207965f4a5049f80736019114f360186ec96b` | clean `master` |
| Cloud Addon | `4978338f4eb36ec9a3cb704e9d84f418202b15b` | clean `master` |
| Adapter | `eb73e503a8c102acd6f87b69f406ee4b94aac85f` | clean topic branch |
| Toolkit | `921d1dc7cb5661fef10ca8f83b781690c98034cb` | clean `master` |
| Toolbox | `ee814e04de54e4774d7851181837ea767e552a14` | clean `master` |
| Eval Lab | `5362655d9f27f9db9a3bcc8a7717e75ac1d9659f` | clean `main` |

## Acceptance evidence

`composer acceptance:cross-repo-release` passed against Core `ed28f98`,
Adapter `eb73e503`, and Toolkit `921d1dc7`. The run passed Core and Adapter
source, WordPress, package, and release gates; the signed M4 Ollama fixture
with persisted provider-log correlation; duplicate execution refusal; Toolkit
PHPStan; official WordPress 6.9.4/PHP 8.0 Docker smoke; and fixture cleanup.
The temporary Ollama loader, SSH tunnel, and provider-log fixture were removed.

## Reproducible package evidence

| Artifact | SHA-256 | Size |
| --- | --- | ---: |
| Core `build/npcink-governance-core.zip` | `71c752a7c100f58a4628646c071b72b1b862011fdaae776a8217d3ba6ad78fc4` | 269,974 bytes |
| Adapter `build/npcink-ai-client-adapter.zip` | `4356f90721d56b7484d42ad4a73b1ed0500295f21580ce75a656b5f9cba68fbc` | 176,899 bytes |
| Toolkit deterministic RC archive | `7b946a3b13c1f79c62b89e2f58d858b5d5e97e5041990b8ed1faa5a631748009` | 1,254,772 bytes |

The Toolkit archive was built twice from the exact `921d1dc7` source using its
`.distignore` rules, normalized timestamps, and `zip -X`; both builds produced
the same digest. Toolkit's official `release:zip` guard remains separate from
this evidence because conventional `0.7.0` points to historical commit
`46c1405422ae0ac1f8ea6820b391b48d09326a14`.

## Publication hold

- Core `v0.3.0` remains at historical commit
  `3357f916899ac4b013a753da6d99e712274f2221`.
- Toolkit `0.7.0` remains at historical commit
  `46c1405422ae0ac1f8ea6820b391b48d09326a14`.
- Adapter `v0.4.3` is available, but no conventional or snapshot tag was
  created.
- Snapshot tag creation/push, WordPress.org SVN publication, production
  deployment, and real-usage kickoff remain separate actions requiring explicit
  authorization.
