#!/usr/bin/env node

import { execSync } from 'child_process';
import { readFileSync, writeFileSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const rootDir = join(__dirname, '..');

function getCurrentDate() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

function escapeRegExp(value) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function updateVersionInFile(filePath, version, patterns) {
  console.log(`Updating version in ${filePath}...`);

  let content = readFileSync(filePath, 'utf8');

  for (const pattern of patterns) {
    const regex = new RegExp(pattern.search, 'g');
    content = content.replace(regex, pattern.replace.replace('${version}', version));
  }

  writeFileSync(filePath, content, 'utf8');
  console.log(`Updated ${filePath}`);
}

function updateChangelog(version) {
  console.log('Updating CHANGELOG.md...');

  const changelogPath = join(rootDir, 'CHANGELOG.md');
  let content = readFileSync(changelogPath, 'utf8');
  const currentDate = getCurrentDate();

  content = content.replace(/## \[Unreleased\]/g, `## [Unreleased]\n\n## [${version}] - ${currentDate}`);

  const unreleasedLinkPattern = /\[unreleased\]: https:\/\/github\.com\/nabasa-dev\/jooosi-mail\/compare\/(.+?)\.\.\.HEAD/;
  const match = content.match(unreleasedLinkPattern);

  if (match) {
    const previousRef = match[1];

    content = content.replace(
      unreleasedLinkPattern,
      `[unreleased]: https://github.com/nabasa-dev/jooosi-mail/compare/${version}...HEAD`,
    );

    const linksSection = content.match(/(\[unreleased\]: .+)$/m);
    const versionLinkPattern = new RegExp(`^\\[${escapeRegExp(version)}\\]:`, 'm');

    if (linksSection && !versionLinkPattern.test(content)) {
      const insertPosition = content.indexOf(linksSection[0]) + linksSection[0].length;
      const newVersionLink = `\n[${version}]: https://github.com/nabasa-dev/jooosi-mail/compare/${previousRef}...${version}`;
      content = content.slice(0, insertPosition) + newVersionLink + content.slice(insertPosition);
    }
  }

  writeFileSync(changelogPath, content, 'utf8');
  console.log('Updated CHANGELOG.md');
}

function executeGitCommands(version) {
  console.log('Creating git commit and tag...');
  execSync('git add .', { stdio: 'inherit', cwd: rootDir });
  execSync(`git commit -m "Update VERSION for ${version}"`, { stdio: 'inherit', cwd: rootDir });
  execSync(`git tag ${version}`, { stdio: 'inherit', cwd: rootDir });
  console.log('Git commit and tag created successfully');
}

function release() {
  const version = process.argv[2];

  if (!version) {
    console.error('Please provide a version number');
    console.log('Usage: node release.js <version>');
    process.exit(1);
  }

  if (!/^\d+\.\d+\.\d+$/.test(version)) {
    console.error('Invalid version format. Please use semantic versioning, for example 1.0.0');
    process.exit(1);
  }

  try {
    updateVersionInFile(join(rootDir, 'readme.txt'), version, [
      {
        search: 'Stable tag: [0-9]+\\.[0-9]+\\.[0-9]+',
        replace: `Stable tag: ${version}`,
      },
    ]);

    updateVersionInFile(join(rootDir, 'constant.php'), version, [
      {
        search: "define\\('JOOOSI_MAIL_VERSION', '[0-9]+\\.[0-9]+\\.[0-9]+'\\);",
        replace: `define('JOOOSI_MAIL_VERSION', '${version}');`,
      },
    ]);

    updateVersionInFile(join(rootDir, 'jooosi-mail.php'), version, [
      {
        search: ' \\* Version:\\s+[0-9]+\\.[0-9]+\\.[0-9]+',
        replace: ` * Version:             ${version}`,
      },
    ]);

    updateVersionInFile(join(rootDir, 'composer.json'), version, [
      {
        search: '"version": "[0-9]+\\.[0-9]+\\.[0-9]+"',
        replace: `"version": "${version}"`,
      },
    ]);

    updateVersionInFile(join(rootDir, 'package.json'), version, [
      {
        search: '"version": "[0-9]+\\.[0-9]+\\.[0-9]+"',
        replace: `"version": "${version}"`,
      },
    ]);

    updateChangelog(version);
    executeGitCommands(version);

    console.log('Release process completed successfully.');
    console.log('Next steps: git push && git push --tags');
  } catch (error) {
    console.error('Error during release process:', error.message);
    process.exit(1);
  }
}

release();
