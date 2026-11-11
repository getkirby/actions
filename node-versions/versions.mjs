import { appendFileSync, readFileSync } from "node:fs";
import { createRequire } from "node:module";
import { join } from "node:path";

const semver = createRequire(join(process.env.RUNNER_TEMP, "semver", "index.js"))("semver");

// the Node versions that can be tested, as far as package.json allows them
const candidates = ["22", "24", "26"];
const range = JSON.parse(readFileSync("package.json", "utf8")).engines?.node;

if (range === undefined) {
	console.log("::error::package.json requires no Node version");
	process.exit(1);
}

const versions = candidates.filter((version) => semver.intersects(range, `${version}.x`));

if (versions.length === 0) {
	console.log(`::error::package.json allows none of the Node versions ${candidates.join(", ")}`);
	process.exit(1);
}

appendFileSync(
	process.env.GITHUB_OUTPUT,
	`versions=${JSON.stringify(versions)}\nlowest=${versions[0]}\n`
);
