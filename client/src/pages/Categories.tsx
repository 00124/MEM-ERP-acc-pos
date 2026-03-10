import React, { useState } from "react";
import { PageTransition, Card, Button, Input, Modal, Badge } from "@/components/ui/core";
import { useCategories, useCreateCategory } from "@/hooks/use-categories";
import { Plus, Layers } from "lucide-react";

export default function Categories() {
  const { data: categories, isLoading } = useCategories();
  const createMutation = useCreateCategory();
  const [isAddOpen, setIsAddOpen] = useState(false);
  const [name, setName] = useState("");

  const handleCreate = (e: React.FormEvent) => {
    e.preventDefault();
    createMutation.mutate({ name, slug: name.toLowerCase().replace(/\s+/g, '-') }, {
      onSuccess: () => {
        setIsAddOpen(false);
        setName("");
      }
    });
  };

  return (
    <PageTransition className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-display font-bold tracking-tight text-slate-900 dark:text-white">Categories</h1>
          <p className="text-slate-500 mt-1">Manage your product taxonomy.</p>
        </div>
        <Button onClick={() => setIsAddOpen(true)} className="gap-2">
          <Plus className="w-5 h-5" /> Add Category
        </Button>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {[1,2,3].map(i => <Card key={i} className="h-24 animate-pulse" />)}
        </div>
      ) : categories?.length === 0 ? (
        <Card className="p-12 text-center flex flex-col items-center justify-center border-dashed">
          <div className="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4">
            <Layers className="w-8 h-8 text-slate-400" />
          </div>
          <h3 className="text-lg font-bold text-slate-900 dark:text-white">No categories yet</h3>
          <p className="text-slate-500 mb-6 max-w-sm">Group your products logically to make them easy to browse.</p>
          <Button onClick={() => setIsAddOpen(true)}>Create Category</Button>
        </Card>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {categories?.map((category) => (
            <Card key={category.id} className="p-5 flex items-center gap-4 hover:shadow-lg transition-all group border-border cursor-pointer">
              <div className="w-14 h-14 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
                 {category.image ? (
                  <img src={category.image} alt={category.name} className="w-full h-full object-cover rounded-xl" />
                ) : (
                  <Layers className="w-6 h-6" />
                )}
              </div>
              <div className="flex-1 min-w-0">
                <h3 className="font-bold text-slate-900 dark:text-white text-lg truncate">{category.name}</h3>
                <div className="flex items-center gap-2 mt-1">
                  <Badge variant="default" className="text-[10px]">/{category.slug}</Badge>
                  {category.parentId && <Badge variant="warning" className="text-[10px]">Subcategory</Badge>}
                </div>
              </div>
            </Card>
          ))}
        </div>
      )}

      <Modal isOpen={isAddOpen} onClose={() => setIsAddOpen(false)} title="Create Category">
        <form onSubmit={handleCreate} className="space-y-4">
          <Input 
            label="Category Name" 
            placeholder="e.g. Electronics, Clothing..." 
            value={name}
            onChange={(e) => setName(e.target.value)}
            required
          />
          <div className="pt-4 flex justify-end gap-3">
            <Button type="button" variant="secondary" onClick={() => setIsAddOpen(false)}>Cancel</Button>
            <Button type="submit" isLoading={createMutation.isPending}>Save Category</Button>
          </div>
        </form>
      </Modal>
    </PageTransition>
  );
}
