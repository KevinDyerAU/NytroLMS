/**
 * AddCompanyDialog — Create a new company
 */
import { useState } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { createCompany } from '@/lib/api';
import { Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface AddCompanyDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
}

export function AddCompanyDialog({ open, onOpenChange, onSaved }: AddCompanyDialogProps) {
  const [saving, setSaving] = useState(false);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');

  const resetForm = () => {
    setName('');
    setEmail('');
    setPhone('');
    setAddress('');
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!name.trim() || !email.trim()) {
      toast.error('Company name and email are required');
      return;
    }

    setSaving(true);
    try {
      await createCompany({
        name: name.trim(),
        email: email.trim(),
        number: phone.trim(),
        address: address.trim() || undefined,
      });

      toast.success(`Company "${name.trim()}" created successfully`);
      resetForm();
      onOpenChange(false);
      onSaved();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to create company');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) resetForm(); onOpenChange(v); }}>
      <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Add New Company</DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-3">
            <div>
              <Label htmlFor="companyName">Company Name *</Label>
              <Input id="companyName" value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Acme Training Pty Ltd" required />
            </div>

            <div>
              <Label htmlFor="companyEmail">Email *</Label>
              <Input id="companyEmail" type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="admin@company.com" required />
            </div>

            <div>
              <Label htmlFor="companyPhone">Phone</Label>
              <Input id="companyPhone" value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="e.g. 02 1234 5678" />
            </div>

            <div>
              <Label htmlFor="companyAddress">Address</Label>
              <Input id="companyAddress" value={address} onChange={(e) => setAddress(e.target.value)} placeholder="e.g. 123 Main St, Sydney NSW 2000" />
            </div>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => { resetForm(); onOpenChange(false); }} disabled={saving}>
              Cancel
            </Button>
            <Button type="submit" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={saving}>
              {saving && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />}
              Create Company
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
