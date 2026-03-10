import type { Express } from "express";
import type { Server } from "http";
import { storage } from "./storage";
import { api } from "@shared/routes";

export async function registerRoutes(
  httpServer: Server,
  app: Express
): Promise<Server> {
  app.get(api.products.list.path, async (req, res) => {
    const items = await storage.getProducts();
    res.json(items);
  });

  app.get(api.products.get.path, async (req, res) => {
    const item = await storage.getProduct(Number(req.params.id));
    if (!item) {
      return res.status(404).json({ message: "Product not found" });
    }
    res.json(item);
  });

  app.get(api.brands.list.path, async (req, res) => {
    const items = await storage.getBrands();
    res.json(items);
  });

  app.get(api.categories.list.path, async (req, res) => {
    const items = await storage.getCategories();
    res.json(items);
  });

  return httpServer;
}
