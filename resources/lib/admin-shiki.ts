let highlighterPromise: Promise<{
  codeToHtml: (code: string, options: { lang: "html" | "json"; theme: "github-light" }) => string
}> | null = null

function getHighlighter() {
  highlighterPromise ??= Promise.all([
    import("@shikijs/core"),
    import("@shikijs/engine-javascript"),
    import("@shikijs/langs/html"),
    import("@shikijs/langs/json"),
    import("@shikijs/themes/github-light"),
  ]).then(([{ createHighlighterCore }, { createJavaScriptRegexEngine }, html, json, githubLight]) =>
    createHighlighterCore({
      engine: createJavaScriptRegexEngine(),
      langs: [html.default, json.default],
      themes: [githubLight.default],
    }),
  )

  return highlighterPromise
}

export async function highlightCode(
  code: string,
  lang: "html" | "json",
): Promise<string> {
  const highlighter = await getHighlighter()

  return highlighter.codeToHtml(code, {
    lang,
    theme: "github-light",
  })
}
