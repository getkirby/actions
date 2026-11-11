import { expect, test } from "vitest";
import { year } from "./year.js";

test("year", () => {
	expect(year(new Date("2026-11-11"))).toBe(2026);
});
