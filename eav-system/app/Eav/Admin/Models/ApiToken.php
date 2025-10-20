<?php

namespace Eav\Admin\Models;

use Core\Database\Model;

class ApiToken extends Model
{
    protected string $table = 'eav_api_tokens';
    protected string $primaryKey = 'token_id';
    protected bool $timestamps = false;
    
    protected array $fillable = [
        'user_id',
        'token_hash',
        'token_name',
        'scopes',
        'expires_at',
        'is_active'
    ];
    
    protected array $casts = [
        'scopes' => 'json',
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean'
    ];
    
    protected array $hidden = [
        'token_hash'
    ];
    
    /**
     * Get the user this token belongs to
     */
    public function user()
    {
        return $this->belongsTo(\User\Model\User::class, 'user_id', 'id');
    }
    
    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        
        return strtotime($this->expires_at) < time();
    }
    
    /**
     * Check if token is valid (active and not expired)
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }
    
    /**
     * Check if token has specific scope
     */
    public function hasScope(string $scope): bool
    {
        $scopes = $this->scopes ?? [];
        return in_array($scope, $scopes) || in_array('*', $scopes);
    }
    
    /**
     * Update last used timestamp
     */
    public function markAsUsed(): void
    {
        $this->last_used_at = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Revoke the token
     */
    public function revoke(): void
    {
        $this->is_active = false;
        $this->save();
    }
    
    /**
     * Generate a new token
     */
    public static function generate(int $userId, string $name, array $scopes, ?int $expiryDays = null): array
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        
        $expiresAt = null;
        if ($expiryDays !== null) {
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));
        }
        
        $apiToken = new self();
        $apiToken->user_id = $userId;
        $apiToken->token_hash = $hash;
        $apiToken->token_name = $name;
        $apiToken->scopes = $scopes;
        $apiToken->expires_at = $expiresAt;
        $apiToken->created_at = date('Y-m-d H:i:s');
        $apiToken->save();
        
        return [
            'token' => $token,
            'model' => $apiToken
        ];
    }
    
    /**
     * Verify a token string
     */
    public static function verify(string $token): ?self
    {
        $hash = hash('sha256', $token);
        $apiToken = self::where('token_hash', $hash)->first();
        
        if (!$apiToken || !$apiToken->isValid()) {
            return null;
        }
        
        return $apiToken;
    }
}
