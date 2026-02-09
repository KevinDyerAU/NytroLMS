/**
 * AddRoleDialog — Create a new role in the system
 */
import { useState } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { supabase } from '@/lib/supabase';
import { Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface AddRoleDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
}

export function AddRoleDialog({ open, onOpenChange, onSaved }: AddRoleDialogProps) {
  const [saving, setSaving] = useState(false);
  const [roleName, setRoleName] = useState('');
  const [guardName, setGuardName] = useState('web');

  const resetForm = () => {
    setRoleName('');
    setGuardName('web');
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!roleName.trim()) {
      toast.error('Role name is required');
      return;
    }

    setSaving(true);
    try {
      // Check if role already exists
      const { data: existing } = await supabase
        .from('roles')
        .select('id')
        .eq('name', roleName.trim())
        .maybeSingle();

      if (existing) {
        toast.error(`Role "${roleName.trim()}" already exists`);
        setSaving(false);
        return;
      }

      const { error } = await supabase.from('roles').insert({
        name: roleName.trim(),
        guard_name: guardName.trim() || 'web',
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      });

      if (error) throw error;

      toast.success(`Role "${roleName.trim()}" created successfully`);
      resetForm();
      onOpenChange(false);
      onSaved();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to create role');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) resetForm(); onOpenChange(v); }}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>Add New Role</DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-3">
            <div>
              <Label htmlFor="roleName">Role Name *</Label>
              <Input
                id="roleName"
                value={roleName}
                onChange={(e) => setRoleName(e.target.value)}
                placeholder="e.g. Supervisor, Manager"
                required
              />
              <p className="text-xs text-[#94a3b8] mt-1">
                This will be the display name for the role
              </p>
            </div>

            <div>
              <Label htmlFor="guardName">Guard Name</Label>
              <Input
                id="guardName"
                value={guardName}
                onChange={(e) => setGuardName(e.target.value)}
                placeholder="web"
              />
              <p className="text-xs text-[#94a3b8] mt-1">
                Authentication guard (usually "web")
              </p>
            </div>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => { resetForm(); onOpenChange(false); }} disabled={saving}>
              Cancel
            </Button>
            <Button type="submit" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={saving}>
              {saving && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />}
              Create Role
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
