# Bilingual Publishing Notes

## English

Npcink Governance Core uses the `npcink-governance-core` text domain in runtime PHP strings, so
it is prepared for WordPress translation workflows.

For WordPress.org publishing, use `listing-copy-en.md` as the primary plugin
directory copy. Use `listing-copy-zh.md` for Chinese launch posts,
documentation, marketplace-adjacent pages, or as the source for future Chinese
translation work.

Bundled translation files now live in the standard `languages/` directory.
Keep generated `.pot`, `.po`, and `.mo` files separate from this `sj`
publishing workspace.

Recommended release flow:

1. Keep source code strings in English.
2. Keep all runtime strings wrapped with the `npcink-governance-core` text domain.
3. Regenerate `languages/npcink-governance-core.pot` before release when
   runtime strings change.
4. Translate Chinese strings through the project-owned
   `languages/npcink-governance-core-zh_CN.po`; WordPress.org translation can
   still be used later as the public translation workflow.
5. Keep `sj/` for listing copy, image prompts, and release artwork only.

## Chinese

Npcink Governance Core 的 PHP 运行时字符串使用 `npcink-governance-core` text domain，因此已经
具备接入 WordPress 翻译流程的基础。

发布到 WordPress.org 时，建议使用 `listing-copy-en.md` 作为插件目录主文案。
`listing-copy-zh.md` 用于中文发布文章、中文文档、国内渠道页面，或作为未来中文
翻译工作的源稿。

插件包内置翻译文件现在放在标准 `languages/` 目录。生成的 `.pot`、`.po`、
`.mo` 文件应继续和当前 `sj` 发布素材工作区分开。

推荐发布流程：

1. 源代码字符串继续保持英文。
2. 所有运行时字符串继续使用 `npcink-governance-core` text domain。
3. 运行时字符串变化后，发布前重新生成 `languages/npcink-governance-core.pot`。
4. 中文翻译先维护项目自己的 `languages/npcink-governance-core-zh_CN.po`；
   后续仍可接入 WordPress.org 公共翻译流程。
5. `sj/` 只用于上架文案、图片提示词和发布素材。
