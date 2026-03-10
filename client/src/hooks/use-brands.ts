import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { api } from "@shared/routes";
import type { Brand } from "@shared/schema";
import { useToast } from "@/hooks/use-toast";

export function useBrands() {
  return useQuery({
    queryKey: [api.brands.list.path],
    queryFn: async () => {
      const res = await fetch(api.brands.list.path, { credentials: "include" });
      if (!res.ok) throw new Error("Failed to fetch brands");
      return api.brands.list.responses[200].parse(await res.json());
    },
  });
}

// UI mock mutation
export function useCreateBrand() {
  const queryClient = useQueryClient();
  const { toast } = useToast();
  
  return useMutation({
    mutationFn: async (data: Partial<Brand>) => {
      const res = await fetch("/api/brands", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
        credentials: "include",
      });
      if (!res.ok) throw new Error("API endpoint not implemented. This is a UI mockup.");
      return res.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [api.brands.list.path] });
      toast({ title: "Brand Created" });
    },
    onError: (err) => {
      toast({ title: "Operation Failed", description: err.message, variant: "destructive" });
    }
  });
}
