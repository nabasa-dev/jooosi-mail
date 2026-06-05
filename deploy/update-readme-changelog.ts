#!/usr/bin/env -S deno run --allow-read --allow-write

declare const Deno: {
  readTextFile(path: string): Promise<string>;
  writeTextFile(path: string, data: string): Promise<void>;
  exit(code?: number): never;
};

interface ChangelogEntry {
  version: string;
  date: string;
  content: string;
}

function parseChangelog(changelogContent: string): ChangelogEntry[] {
  const entries: ChangelogEntry[] = [];
  const lines = changelogContent.split('\n');
  let currentEntry: ChangelogEntry | null = null;
  let inEntry = false;

  for (const line of lines) {
    const versionMatch = line.match(/^## \[(.+?)\] - (.+)$/);

    if (versionMatch) {
      if (currentEntry) {
        entries.push(currentEntry);
      }

      currentEntry = {
        version: versionMatch[1],
        date: versionMatch[2],
        content: '',
      };
      inEntry = true;
      continue;
    }

    if (line.startsWith('## ') && !line.match(/^## \[.+?\] - .+$/)) {
      inEntry = false;
      continue;
    }

    if (line.startsWith('# ') || line.startsWith('All notable changes')) {
      continue;
    }

    if (line.match(/^\[.+?\]: https?:\/\//)) {
      continue;
    }

    if (inEntry && currentEntry) {
      let processedLine = line;

      if (line.startsWith('### ')) {
        const sectionName = line.replace('### ', '').trim();
        processedLine = `\n**${sectionName}**\n`;
      }

      if (line.startsWith('- ')) {
        processedLine = `* ${line.substring(2).trim()}`;
      }

      if (processedLine.trim()) {
        currentEntry.content += (currentEntry.content ? '\n' : '') + processedLine;
      }
    }
  }

  if (currentEntry) {
    entries.push(currentEntry);
  }

  return entries;
}

function formatForReadme(entries: ChangelogEntry[]): string {
  let result = '';

  for (const entry of entries) {
    result += `= ${entry.version} - ${entry.date} =\n`;

    if (entry.content.trim()) {
      result += entry.content + '\n';
    }

    result += '\n';
  }

  return result.trim();
}

function updateReadmeWithChangelog(readmeContent: string, changelogText: string): string {
  const changelogStartMatch = readmeContent.match(/== Changelog ==/);

  if (!changelogStartMatch) {
    throw new Error('Could not find "== Changelog ==" section in readme.txt');
  }

  const changelogStart = changelogStartMatch.index!;
  const beforeChangelog = readmeContent.substring(0, changelogStart);
  const changelogSection = `== Changelog ==\n\n${changelogText}\n\n[See changelog for all versions.](https://github.com/nabasa-dev/omni-mail/blob/main/CHANGELOG.md)`;

  return beforeChangelog + changelogSection;
}

async function main() {
  try {
    const projectRoot = new URL('..', import.meta.url).pathname;
    const changelogPath = `${projectRoot}/CHANGELOG.md`;
    const readmePath = `${projectRoot}/readme.txt`;
    const changelogContent = await Deno.readTextFile(changelogPath);
    const readmeContent = await Deno.readTextFile(readmePath);
    const entries = parseChangelog(changelogContent);
    const readmeChangelog = formatForReadme(entries);
    const updatedReadme = updateReadmeWithChangelog(readmeContent, readmeChangelog);

    await Deno.writeTextFile(readmePath, updatedReadme);
  } catch (error) {
    const message = error instanceof Error ? error.message : String(error);

    console.error('Error:', message);
    Deno.exit(1);
  }
}

if ((import.meta as ImportMeta & { main: boolean }).main) {
  await main();
}
