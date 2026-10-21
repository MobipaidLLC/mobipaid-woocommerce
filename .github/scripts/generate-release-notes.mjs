#!/usr/bin/env node

/**
 * Release Notes Generator
 *
 * Automatically parses Git commit history and PR metadata
 * to generate populated and categorized release notes without commit hashes.
 */

import { execSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, "../..");

function getPluginVersion() {
 try {
  const mainPhp = fs.readFileSync(path.join(repoRoot, "mobipaid/mobipaid.php"), "utf8");
  const match = mainPhp.match(/Version:\s*([0-9.]+)/i);
  if (match) return match[1];
 } catch {}

 try {
  const readmeTxt = fs.readFileSync(path.join(repoRoot, "mobipaid/readme.txt"), "utf8");
  const match = readmeTxt.match(/Stable tag:\s*([0-9.]+)/i);
  if (match) return match[1];
 } catch {}

 try {
  const pkgJson = JSON.parse(fs.readFileSync(path.join(repoRoot, "package.json"), "utf8"));
  if (pkgJson.version) return pkgJson.version;
 } catch {}

 return "1.0.0";
}

function getPreviousTag(currentVersion) {
 try {
  const rawVersion = currentVersion.startsWith("v") ? currentVersion : `v${currentVersion}`;
  const tags = execSync("git tag --sort=-creatordate", {
   cwd: repoRoot,
   encoding: "utf8",
   stdio: ["pipe", "pipe", "ignore"],
  })
   .split("\n")
   .map((t) => t.trim())
   .filter(Boolean);
  const priorTag = tags.find((t) => t !== rawVersion);
  return priorTag || null;
 } catch {
  return null;
 }
}

function getCommits(previousTag) {
 try {
  const range = previousTag ? `${previousTag}..HEAD` : "-n 50";
  const log = execSync(`git log ${range} --pretty=format:"%h|%s|%an"`, {
   cwd: repoRoot,
   encoding: "utf8",
   stdio: ["pipe", "pipe", "ignore"],
  });

  return log
   .split("\n")
   .map((line) => line.trim())
   .filter(Boolean)
   .map((line) => {
    const [hash, subject, author] = line.split("|");
    return {
     hash: hash || "",
     subject: subject || "",
     author: author || "",
    };
   });
 } catch {
  return [];
 }
}

function getPREventData() {
 const eventPath = process.env.GITHUB_EVENT_PATH;
 if (!eventPath || !fs.existsSync(eventPath)) return null;

 try {
  return JSON.parse(fs.readFileSync(eventPath, "utf8"));
 } catch {
  return null;
 }
}

function categorizeCommits(commits) {
 const categories = {
  features: [],
  fixes: [],
  refactorChores: [],
  securityPerf: [],
  breaking: [],
  tests: [],
  docs: [],
 };

 const ignoredPrefixes = [/^merge\s/i, /^release\sv/i, /^bump\sv/i];

 for (const commit of commits) {
  const { subject } = commit;
  if (!subject || ignoredPrefixes.some((rx) => rx.test(subject))) continue;

  // Format commit item without commit hash
  const formattedItem = `- ${subject}`;

  // Check breaking change
  if (subject.includes("BREAKING CHANGE") || /^[a-z]+(\([a-z0-9_-]+\))?!:/.test(subject)) {
   categories.breaking.push(formattedItem);
  }

  if (/^feat(\([a-z0-9_-]+\))?:/i.test(subject)) {
   categories.features.push(formattedItem);
  } else if (/^(fix|bugfix|hotfix)(\([a-z0-9_-]+\))?:/i.test(subject)) {
   categories.fixes.push(formattedItem);
  } else if (/^(perf|performance|security|sec)(\([a-z0-9_-]+\))?:/i.test(subject)) {
   categories.securityPerf.push(formattedItem);
  } else if (/^(refactor|chore|build|ci|deps)(\([a-z0-9_-]+\))?:/i.test(subject)) {
   categories.refactorChores.push(formattedItem);
  } else if (/^test(\([a-z0-9_-]+\))?:/i.test(subject)) {
   categories.tests.push(formattedItem);
  } else if (/^docs(\([a-z0-9_-]+\))?:/i.test(subject)) {
   categories.docs.push(formattedItem);
  } else {
   categories.refactorChores.push(formattedItem);
  }
 }

 return categories;
}

export function generateReleaseNotes() {
 const version = process.env.RELEASE_VERSION || getPluginVersion();
 const rawVersion = version.startsWith("v") ? version : `v${version}`;
 const previousTag = getPreviousTag(rawVersion);
 const commits = getCommits(previousTag);
 const categories = categorizeCommits(commits);
 const prData = getPREventData();

 // Determine Objective
 let objective = process.env.RELEASE_OBJECTIVE?.trim();
 if (!objective && prData?.pull_request?.body) {
  const prBody = prData.pull_request.body;
  const match = prBody.match(/### Objective\s*\n+([^#]+)/i);
  if (match && match[1].trim()) {
   objective = match[1].trim();
  }
 }
 if (!objective) {
  objective = `Production release ${rawVersion} containing verified feature enhancements, bug fixes, security & performance optimizations, and codebase maintenance.`;
 }

 const repoSlug = process.env.GITHUB_REPOSITORY || "MobipaidLLC/mobipaid-woocommerce";
 const changelogUrl = previousTag
  ? `https://github.com/${repoSlug}/compare/${previousTag}...${rawVersion}`
  : `https://github.com/${repoSlug}/commits/${rawVersion}`;

 const formatSection = (title, items, defaultText = "- None in this release.") => {
  const content = items.length > 0 ? items.join("\n") : defaultText;
  return `### ${title}\n\n${content}`;
 };

 const sections = [
  `## Version Release: ${rawVersion}`,
  `### Objective\n\n${objective}`,
  formatSection("Features", categories.features),
  formatSection("Bug Fixes", categories.fixes),
  formatSection("Refactoring & Chores", categories.refactorChores),
  formatSection("Security & Performance", categories.securityPerf),
  formatSection("Breaking Changes", categories.breaking),
  `**Full Changelog**: ${changelogUrl}`,
 ];

 return sections.join("\n\n");
}

if (process.argv[1] && process.argv[1].endsWith("generate-release-notes.mjs")) {
 const notes = generateReleaseNotes();
 console.log(notes);
}
