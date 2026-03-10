import { defineConfig } from "drizzle-kit";

export default defineConfig({
  out: "./migrations",
  schema: "./shared/schema.ts",
  dialect: "mysql",
  dbCredentials: {
    url: process.env.DATABASE_URL || "mysql://u931777367_MEMERP:%3F3Xcyo%2FYc@193.203.168.212:3306/u931777367_MEMERPDB",
  },
});
