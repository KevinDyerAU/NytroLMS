/**
 * LoginDialog - Modal login form matching NytroAI design
 */
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { Eye, EyeOff, AlertCircle, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { toast } from 'sonner';

interface LoginDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function LoginDialog({ open, onOpenChange }: LoginDialogProps) {
  const navigate = useNavigate();
  const { login, isLoading } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  const [rememberMe, setRememberMe] = useState(false);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage('');

    if (!email.trim()) {
      setErrorMessage('Email is required');
      return;
    }
    if (!password.trim()) {
      setErrorMessage('Password is required');
      return;
    }

    const result = await login(email, password);
    if (result.success) {
      toast.success('Welcome back!');
      onOpenChange(false);
      navigate('/dashboard');
    } else {
      setErrorMessage(result.error || 'Login failed. Please try again.');
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md p-0 overflow-hidden border-0 rounded-2xl">
        {/* Header with gradient */}
        <div className="bg-gradient-auth px-6 pt-6 pb-4">
          <DialogTitle className="text-2xl font-bold text-white font-heading">Sign In</DialogTitle>
          <p className="text-blue-100 mt-1 text-sm">Welcome back to KeyLMS</p>
        </div>

        {/* Form */}
        <div className="px-6 pb-6 pt-4">
          {errorMessage && (
            <div className="flex items-center gap-2 p-3 mb-4 rounded-lg bg-red-50 border border-red-200">
              <AlertCircle className="h-4 w-4 text-red-500 flex-shrink-0" />
              <p className="text-sm text-red-600">{errorMessage}</p>
            </div>
          )}

          <form onSubmit={handleLogin} className="space-y-5">
            <div>
              <Label htmlFor="login-email" className="text-[#1e293b] font-semibold text-sm">
                Email
              </Label>
              <Input
                id="login-email"
                type="email"
                placeholder="you@example.com"
                value={email}
                onChange={(e) => { setEmail(e.target.value); setErrorMessage(''); }}
                className="mt-1.5 border-[#dbeafe] focus:border-[#3b82f6] h-10"
              />
            </div>

            <div>
              <Label htmlFor="login-password" className="text-[#1e293b] font-semibold text-sm">
                Password
              </Label>
              <div className="relative mt-1.5">
                <Input
                  id="login-password"
                  type={showPassword ? 'text' : 'password'}
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => { setPassword(e.target.value); setErrorMessage(''); }}
                  className="border-[#dbeafe] focus:border-[#3b82f6] pr-10 h-10"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-[#94a3b8] hover:text-[#3b82f6] transition-colors"
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
            </div>

            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <input
                  id="remember"
                  type="checkbox"
                  checked={rememberMe}
                  onChange={(e) => setRememberMe(e.target.checked)}
                  className="w-4 h-4 rounded border-[#dbeafe] accent-[#3b82f6]"
                />
                <Label htmlFor="remember" className="text-sm text-[#64748b] cursor-pointer">
                  Remember me
                </Label>
              </div>
              <button type="button" className="text-sm text-[#3b82f6] hover:underline">
                Forgot password?
              </button>
            </div>

            <Button
              type="submit"
              className="w-full bg-[#3b82f6] hover:bg-[#2563eb] text-white font-semibold py-2.5 h-auto rounded-lg"
              disabled={isLoading}
            >
              {isLoading ? 'Signing in...' : 'Sign In'}
            </Button>

            {/* Demo credentials hint */}
            <div className="bg-[#f8fafc] rounded-lg p-3 border border-[#e2e8f0]">
              <p className="text-xs text-[#94a3b8] font-medium mb-1">Demo Credentials:</p>
              <p className="text-xs text-[#64748b]">admin@keylms.com / admin123</p>
              <p className="text-xs text-[#64748b]">trainer@keylms.com / trainer123</p>
            </div>
          </form>
        </div>
      </DialogContent>
    </Dialog>
  );
}
