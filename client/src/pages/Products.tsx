import React, { useState } from "react";
import { PageTransition, Card, Button, Input, Modal, Badge } from "@/components/ui/core";
import { useProducts, useCreateProduct, useDeleteProduct } from "@/hooks/use-products";
import { Plus, Search, MoreVertical, Trash2, Edit } from "lucide-react";
import { formatDate } from "@/lib/utils";

export default function Products() {
  const { data: products, isLoading } = useProducts();
  const createMutation = useCreateProduct();
  const deleteMutation = useDeleteProduct();
  
  const [isAddOpen, setIsAddOpen] = useState(false);
  const [search, setSearch] = useState("");
  const [formData, setFormData] = useState({ name: '', itemCode: '', productType: 'single' });

  const filteredProducts = products?.filter(p => 
    p.name.toLowerCase().includes(search.toLowerCase()) || 
    p.itemCode.toLowerCase().includes(search.toLowerCase())
  );

  const handleCreate = (e: React.FormEvent) => {
    e.preventDefault();
    createMutation.mutate({ ...formData, slug: formData.name.toLowerCase().replace(/\s+/g, '-') }, {
      onSuccess: () => {
        setIsAddOpen(false);
        setFormData({ name: '', itemCode: '', productType: 'single' });
      }
    });
  };

  return (
    <PageTransition className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-display font-bold tracking-tight text-slate-900 dark:text-white">Products</h1>
          <p className="text-slate-500 mt-1">Manage your inventory and product catalogs.</p>
        </div>
        <Button onClick={() => setIsAddOpen(true)} className="gap-2">
          <Plus className="w-5 h-5" /> Add Product
        </Button>
      </div>

      <Card className="overflow-hidden border-border bg-card">
        <div className="p-4 border-b border-border flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
          <div className="w-full max-w-sm">
            <Input 
              placeholder="Search products..." 
              icon={<Search className="w-4 h-4" />}
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="bg-white dark:bg-slate-950"
            />
          </div>
        </div>
        
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm whitespace-nowrap">
            <thead className="bg-slate-50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 font-semibold border-b border-border">
              <tr>
                <th className="px-6 py-4">Product Name</th>
                <th className="px-6 py-4">Item Code</th>
                <th className="px-6 py-4">Type</th>
                <th className="px-6 py-4">Added On</th>
                <th className="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {isLoading ? (
                <tr>
                  <td colSpan={5} className="px-6 py-8 text-center text-slate-500">Loading products...</td>
                </tr>
              ) : filteredProducts?.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-6 py-8 text-center text-slate-500">No products found. Try a different search.</td>
                </tr>
              ) : (
                filteredProducts?.map(product => (
                  <tr key={product.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-colors">
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 overflow-hidden flex items-center justify-center shrink-0">
                          {product.image ? (
                            <img src={product.image} alt={product.name} className="w-full h-full object-cover" />
                          ) : (
                            <span className="font-bold text-slate-400">{product.name.charAt(0)}</span>
                          )}
                        </div>
                        <div>
                          <p className="font-semibold text-slate-900 dark:text-white">{product.name}</p>
                          <p className="text-xs text-slate-500">Symbology: {product.barcodeSymbology}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 font-mono text-slate-600 dark:text-slate-400">{product.itemCode}</td>
                    <td className="px-6 py-4">
                      <Badge variant={product.productType === 'single' ? 'default' : 'warning'}>{product.productType}</Badge>
                    </td>
                    <td className="px-6 py-4 text-slate-500">{product.createdAt ? formatDate(product.createdAt) : 'N/A'}</td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <Button variant="ghost" size="icon" title="Edit">
                          <Edit className="w-4 h-4 text-slate-500" />
                        </Button>
                        <Button variant="ghost" size="icon" title="Delete" onClick={() => {
                          if(confirm("Are you sure you want to delete this product?")) deleteMutation.mutate(product.id);
                        }}>
                          <Trash2 className="w-4 h-4 text-red-500" />
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </Card>

      <Modal isOpen={isAddOpen} onClose={() => setIsAddOpen(false)} title="Create New Product">
        <form onSubmit={handleCreate} className="space-y-4">
          <Input 
            label="Product Name" 
            placeholder="e.g. Wireless Headphones" 
            value={formData.name}
            onChange={(e) => setFormData({...formData, name: e.target.value})}
            required
          />
          <Input 
            label="Item Code (SKU)" 
            placeholder="e.g. WH-001" 
            value={formData.itemCode}
            onChange={(e) => setFormData({...formData, itemCode: e.target.value})}
            required
          />
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Product Type</label>
            <select 
              className="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
              value={formData.productType}
              onChange={(e) => setFormData({...formData, productType: e.target.value})}
            >
              <option value="single">Single</option>
              <option value="variable">Variable</option>
            </select>
          </div>
          <div className="pt-4 flex justify-end gap-3">
            <Button type="button" variant="secondary" onClick={() => setIsAddOpen(false)}>Cancel</Button>
            <Button type="submit" isLoading={createMutation.isPending}>Save Product</Button>
          </div>
        </form>
      </Modal>
    </PageTransition>
  );
}
