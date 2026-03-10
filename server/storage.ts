import { db } from "./db";
import { products, brands, categories, type Product, type Brand, type Category } from "@shared/schema";
import { eq } from "drizzle-orm";

export interface IStorage {
  getProducts(): Promise<Product[]>;
  getProduct(id: number): Promise<Product | undefined>;
  getBrands(): Promise<Brand[]>;
  getCategories(): Promise<Category[]>;
}

export class DatabaseStorage implements IStorage {
  async getProducts(): Promise<Product[]> {
    return await db.select().from(products);
  }

  async getProduct(id: number): Promise<Product | undefined> {
    const [product] = await db.select().from(products).where(eq(products.id, id));
    return product;
  }

  async getBrands(): Promise<Brand[]> {
    return await db.select().from(brands);
  }

  async getCategories(): Promise<Category[]> {
    return await db.select().from(categories);
  }
}

export const storage = new DatabaseStorage();
