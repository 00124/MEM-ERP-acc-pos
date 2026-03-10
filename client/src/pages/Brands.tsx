import React, { useState } from "react";
import { PageTransition, Card, Button, Input, Modal } from "@/components/ui/core";
import { useBrands, useCreateBrand } from "@/hooks/use-brands";
import { Plus, Tag } from "lucide-react";

export default function Brands() {
  const { data: brands, isLoading } = useBrands();
  const createMutation = useCreateBrand();
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
          <h1 className="text-3xl font-display font-bold tracking-tight text-slate-900 dark:text-white">Brands</h1>
          <p className="text-slate-500 mt-1">Organize products by manufacturer.</p>
        </div>
        <Button onClick={() => setIsAddOpen(true)} className="gap-2">
          <Plus className="w-5 h-5" /> Add Brand
        </Button>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
          {[1,2,3,4].map(i => <Card key={i} className="h-32 animate-pulse" />)}
        </div>
      ) : brands?.length === 0 ? (
        <Card className="p-12 text-center flex flex-col items-center justify-center border-dashed">
          <div className="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4">
            <Tag className="w-8 h-8 text-slate-400" />
          </div>
          <h3 className="text-lg font-bold text-slate-900 dark:text-white">No brands yet</h3>
          <p className="text-slate-500 mb-6 max-w-sm">Create your first brand to help customers filter and find products easier.</p>
          <Button onClick={() => setIsAddOpen(true)}>Create Brand</Button>
        </Card>
      ) : (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
          {brands?.map((brand) => (
            <Card key={brand.id} className="p-6 text-center hover:shadow-lg transition-all group overflow-hidden relative cursor-pointer border-border">
              {/* background decorative pattern */}
              <div className="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity" />
              
              <div className="w-16 h-16 mx-auto bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-sm">
                {brand.image ? (
                  <img src={brand.image} alt={brand.name} className="w-full h-full object-contain rounded-2xl p-2" />
                ) : (
                  <span className="text-2xl font-bold font-display text-slate-400 group-hover:text-indigo-500 transition-colors">{brand.name.charAt(0)}</span>
                )}
              </div>
              <h3 className="font-bold text-slate-900 dark:text-white truncate">{brand.name}</h3>
              <p className="text-xs text-slate-500 mt-1 truncate">/{brand.slug}</p>
            </Card>
          ))}
        </div>
      )}

      <Modal isOpen={isAddOpen} onClose={() => setIsAddOpen(false)} title="Create Brand">
        <form onSubmit={handleCreate} className="space-y-4">
          <Input 
            label="Brand Name" 
            placeholder="e.g. Apple, Samsung..." 
            value={name}
            onChange={(e) => setName(e.target.value)}
            required
          />
          <div className="pt-4 flex justify-end gap-3">
            <Button type="button" variant="secondary" onClick={() => setIsAddOpen(false)}>Cancel</Button>
            <Button type="submit" isLoading={createMutation.isPending}>Save Brand</Button>
          </div>
        </form>
      </Modal>
    </PageTransition>
  );
}
