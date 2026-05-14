<?php

namespace App\Repositories;

use App\Models\Member;

class MemberRepository extends Repository
{
    /**
     * 更新會員資料
     * 
     * @param \App\Models\Member $member
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateByEloquentModel(Member $member, array $data): bool
    {
        return $member->update($data);
    }
}
