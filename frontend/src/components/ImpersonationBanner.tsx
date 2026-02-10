/**
 * ImpersonationBanner — Sticky banner shown when an admin is impersonating a student.
 * Displays the impersonated user's name and a button to stop impersonating.
 */
import { useAuth } from '@/contexts/AuthContext';
import { Button } from '@/components/ui/button';
import { Eye, X } from 'lucide-react';

export function ImpersonationBanner() {
  const { isImpersonating, impersonatedUser, stopImpersonating, realUser } = useAuth();

  if (!isImpersonating || !impersonatedUser) return null;

  return (
    <div className="fixed top-0 left-0 right-0 z-[100] bg-amber-500 text-white px-4 py-2 shadow-md">
      <div className="max-w-7xl mx-auto flex items-center justify-between">
        <div className="flex items-center gap-2 text-sm font-medium">
          <Eye className="w-4 h-4" />
          <span>
            Viewing as <strong>{impersonatedUser.name}</strong> ({impersonatedUser.email})
          </span>
          {realUser && (
            <span className="text-amber-100 ml-1">
              — Logged in as {realUser.name}
            </span>
          )}
        </div>
        <Button
          variant="ghost"
          size="sm"
          className="h-7 text-white hover:bg-amber-600 hover:text-white"
          onClick={stopImpersonating}
        >
          <X className="w-3.5 h-3.5 mr-1" />
          Stop Impersonating
        </Button>
      </div>
    </div>
  );
}
